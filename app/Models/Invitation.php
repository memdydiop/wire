<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable; // Trait essentiel pour les notifications
use Spatie\Permission\Models\Role;

class Invitation extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'token',
        'role',
        'sent_by',
        'accepted_at',
        'expires_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'status',
        'status_label',
        'time_until_expiry',
        'is_valid',
    ];

    // Constantes
    public const DEFAULT_ROLE = 'user';
    public const DEFAULT_EXPIRY_DAYS = 7;
    public const MAX_ATTEMPTS = 15;
    public const ATTEMPTS_WARNING_THRESHOLD = 5;

    // ========== CONFIGURATION NOTIFICATION ==========

    /**
     * Définit l'email destinataire pour les notifications
     */
    public function routeNotificationForMail($notification)
    {
        return $this->email;
    }

    // ========== BOOT ==========
    
    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function (self $invitation) {
            // Génération automatique si non fourni
            $invitation->token = $invitation->token ?? self::generateToken();
            $invitation->expires_at = $invitation->expires_at ?? now()->addDays(self::DEFAULT_EXPIRY_DAYS);
            $invitation->role = $invitation->role ?? self::DEFAULT_ROLE;
        });

        static::created(function (self $invitation) {
            Log::info('Invitation créée', [
                'id' => $invitation->id, 
                'email' => $invitation->email
            ]);
        });
    }

    // ========== RELATIONS ==========
    
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by')->withDefault([
            'name' => 'Système',
            'email' => 'system@app.com',
        ]);
    }

    // ========== SCOPES ==========
    
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now())->whereNull('accepted_at');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now())->whereNull('accepted_at');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('accepted_at');
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->whereNotNull('accepted_at');
    }

    public function scopeForEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', strtolower($email));
    }

    // ========== MÉTHODES MÉTIER (CRUCIALES) ==========
    
    /**
     * Génère un token unique
     */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(64);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    /**
     * Création d'une nouvelle invitation avec validations
     */
    public static function createNew(
        string $email, 
        string $role, 
        int $sentById, 
        int $expiryDays = self::DEFAULT_EXPIRY_DAYS // <--- Ajout du paramètre manquant
    ): self 
    {
        // 1. Validation format email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email invalide.');
        }
        $email = strtolower(trim($email));

        // 2. Vérifier si l'utilisateur existe déjà
        if (User::where('email', $email)->exists()) {
            throw new \RuntimeException('Cet utilisateur possède déjà un compte.');
        }

        // 3. Vérifier s'il a déjà une invitation en cours (valide)
        $existing = self::where('email', $email)->valid()->first();
        if ($existing) {
            throw new \RuntimeException("Une invitation est déjà en cours pour cet email (Expire {$existing->expires_at->diffForHumans()}).");
        }

        // 4. Vérifier le rôle
        if (!Role::where('name', $role)->exists()) {
            throw new \InvalidArgumentException("Le rôle '{$role}' n'existe pas.");
        }

        // 5. Création
        return self::create([
            'email' => $email,
            'role' => $role,
            'sent_by' => $sentById,
            // On force la date d'expiration ici
            'expires_at' => now()->addDays($expiryDays),
            // Le token est généré automatiquement par le boot() s'il n'est pas précisé, 
            // ou vous pouvez le forcer ici : 'token' => self::generateToken(),
        ]);
    }

    /**
     * Renvoyer une invitation (prolonger validité + nouveau token)
     */
    public function resend(int $additionalDays = self::DEFAULT_EXPIRY_DAYS): void
    {
        if ($this->isAccepted()) {
            throw new \RuntimeException('Impossible de renvoyer une invitation acceptée.');
        }

        DB::transaction(function () use ($additionalDays) {
            $this->resetAttempts();
            
            $this->update([
                'token' => self::generateToken(),
                'expires_at' => now()->addDays($additionalDays)
            ]);
            
            // Rafraîchir l'instance pour que le mail parte avec le nouveau token
            $this->refresh();

            Log::info('Invitation renvoyée', ['id' => $this->id]);
        });
    }

    public function revoke(): void
    {
        if ($this->isAccepted()) throw new \RuntimeException('Déjà acceptée.');

        DB::transaction(function () {
            $this->update(['expires_at' => now(), 'token' => null]);
            $this->resetAttempts();
        });
    }

    public function markAsAccepted(): void
    {
        if ($this->isAccepted()) throw new \RuntimeException('Déjà acceptée.');
        if (!$this->isValid()) throw new \RuntimeException('Invitation invalide.');

        DB::transaction(function () {
            $this->resetAttempts();
            $this->update(['accepted_at' => now(), 'token' => null]);
        });
    }

    // ========== GETTERS & ATTRIBUTS ==========

    public function isValid(): bool
    {
        return $this->accepted_at === null 
            && $this->expires_at->isFuture()
            && !$this->isTokenCompromised();
    }

    public function getIsValidAttribute(): bool { return $this->isValid(); }
    public function isExpired(): bool { return $this->expires_at->isPast() && is_null($this->accepted_at); }
    public function isAccepted(): bool { return !is_null($this->accepted_at); }
    public function isPending(): bool { return is_null($this->accepted_at) && !$this->isExpired(); }

    public function getStatus(): string
    {
        if ($this->isAccepted()) return 'accepted';
        if ($this->isExpired()) return 'expired';
        return 'pending';
    }

    public function getStatusAttribute(): string { return $this->getStatus(); }
    public function getStatusLabelAttribute(): string
    {
        return match($this->getStatus()) {
            'accepted' => 'Acceptée',
            'expired' => 'Expirée',
            default => 'En attente',
        };
    }

    public function generateInvitationUrl(): string
    {
        return route('register.invitee', ['token' => $this->token]);
    }

    public function timeUntilExpiry(): string
    {
        return $this->isExpired() ? 'Expirée' : $this->expires_at->diffForHumans();
    }

    public function getTimeUntilExpiryAttribute(): string { return $this->timeUntilExpiry(); }

    // ========== SÉCURITÉ (CACHE TENTATIVES) ==========
    
    private function getAttemptsCacheKey(): string { return "invitation_attempts:{$this->id}"; }
    public function getAttemptsCount(): int { return Cache::get($this->getAttemptsCacheKey(), 0); }
    public function resetAttempts(): void { Cache::forget($this->getAttemptsCacheKey()); }
    public function isTokenCompromised(): bool { return $this->getAttemptsCount() > self::MAX_ATTEMPTS; }
    
    public function incrementAttempts(): void
    {
        $key = $this->getAttemptsCacheKey();
        $attempts = Cache::get($key, 0) + 1;
        Cache::put($key, $attempts, now()->addHour());
    }

    // ========== EXPORT ==========

    public function toArray(): array
    {
        $array = parent::toArray();
        $array['invitation_url'] = $this->generateInvitationUrl();
        $array['status_label'] = $this->status_label;
        $array['sender_name'] = $this->sender->name ?? 'Système';
        return $array;
    }
}