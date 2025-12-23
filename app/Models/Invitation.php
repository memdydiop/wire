<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
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
    ];

    // Constantes
    public const DEFAULT_ROLE = 'user';
    public const DEFAULT_EXPIRY_DAYS = 1; // Augmenté à 7 par défaut pour être cohérent
    public const MAX_ATTEMPTS = 15;

    // ========== BOOT ==========
    
    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function (self $invitation) {
            $invitation->token = $invitation->token ?? self::generateToken();
            // CORRECTION CRITIQUE : addDays au lieu de addMinutes
            $invitation->expires_at = $invitation->expires_at ?? now()->addDays(self::DEFAULT_EXPIRY_DAYS);
            $invitation->role = $invitation->role ?? self::DEFAULT_ROLE;
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

    // ========== LOGIQUE MÉTIER ==========
    
    public static function generateToken(): string
    {
        do {
            $token = Str::random(64);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    public static function createNew(string $email, string $role, int $sentById, int $expiryDays = self::DEFAULT_EXPIRY_DAYS): self 
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email invalide.');
        }
        $email = strtolower(trim($email));

        if (User::where('email', $email)->exists()) {
            throw new \RuntimeException('Cet utilisateur possède déjà un compte.');
        }

        $existing = self::where('email', $email)->valid()->first();
        if ($existing) {
            throw new \RuntimeException("Une invitation valide existe déjà (Expire {$existing->expires_at->diffForHumans()}).");
        }

        // Vérification de sécurité pour le rôle
        if (!Role::where('name', $role)->exists()) {
             // Fallback safe si le rôle n'existe pas
             $role = self::DEFAULT_ROLE;
        }

        return self::create([
            'email' => $email,
            'role' => $role,
            'sent_by' => $sentById,
            'expires_at' => now()->addDays($expiryDays),
        ]);
    }

    public function resend(?int $additionalDays = null): void
    {
        if ($this->isAccepted()) {
            throw new \RuntimeException('Impossible de renvoyer une invitation acceptée.');
        }

        $days = $additionalDays ?? self::DEFAULT_EXPIRY_DAYS;

        DB::transaction(function () use ($days) {
            $this->resetAttempts();
            $this->update([
                'token' => self::generateToken(),
                'expires_at' => now()->addDays($days)
            ]);
            // Pas besoin de refresh() si on update l'instance courante via Eloquent, 
            // mais c'est une sécurité si des triggers DB existent.
        });
        
        Log::info('Invitation renvoyée', ['id' => $this->id]);
    }

    public function revoke(): void
    {
        if ($this->isAccepted()) return; // Idempotence : si déjà accepté, on ne fait rien sans erreur

        DB::transaction(function () {
            // On invalide le token et on expire la date
            $this->update(['expires_at' => now()->subSecond(), 'token' => null]);
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

    // ========== ACCESSORS UI ==========

    /**
     * Retourne les données pour le badge de statut (Flux UI)
     * Déplace la logique de la Vue vers le Modèle
     */
    protected function statusBadge(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->isAccepted()) {
                    return [
                        'variant' => 'success',
                        'label' => 'Acceptée',
                        'date_label' => 'Validée le',
                        'date' => $this->accepted_at
                    ];
                }
                if ($this->isExpired()) {
                    return [
                        'variant' => 'danger',
                        'label' => 'Expirée',
                        'date_label' => 'Expirée le',
                        'date' => $this->expires_at
                    ];
                }
                return [
                    'variant' => 'warning',
                    'label' => 'En attente',
                    'date_label' => 'Expire le',
                    'date' => $this->expires_at
                ];
            }
        );
    }

    // ========== HELPERS ==========

    public function isValid(): bool
    {
        return $this->accepted_at === null 
            && $this->expires_at->isFuture()
            && !$this->isTokenCompromised();
    }

    public function isExpired(): bool { return $this->expires_at->isPast() && is_null($this->accepted_at); }
    public function isAccepted(): bool { return !is_null($this->accepted_at); }

    public function generateInvitationUrl(): string
    {
        return route('register.invitee', ['token' => $this->token]);
    }

    // ========== SÉCURITÉ ==========
    
    private function getAttemptsCacheKey(): string { return "invitation_attempts:{$this->id}"; }
    public function getAttemptsCount(): int { return Cache::get($this->getAttemptsCacheKey(), 0); }
    public function resetAttempts(): void { Cache::forget($this->getAttemptsCacheKey()); }
    public function isTokenCompromised(): bool { return $this->getAttemptsCount() > self::MAX_ATTEMPTS; }
    
    public function incrementAttempts(): void
    {
        $key = $this->getAttemptsCacheKey();
        Cache::put($key, Cache::get($key, 0) + 1, now()->addHour());
    }
    
    /**
     * Retourne le nombre de jours restants avant expiration
     */
    public function daysRemaining(): int
    {
        if ($this->isExpired() || $this->isAccepted()) {
            return 0;
        }
        return $this->expires_at->diffInDays(now());
    }
}