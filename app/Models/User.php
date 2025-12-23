<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\Notifiable;
use Propaganistas\LaravelPhone\PhoneNumber;
use Spatie\Activitylog\Traits\LogsActivity;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable, LogsActivity;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'username',
        'avatar_url', 'address', 'city', 'country',
        'last_login_at', 'last_login_ip', 'suspended_at', 'email_verified_at',
    ];

    protected $hidden = [
        'password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token',
    ];

    protected $appends = [
        'full_name_with_status',
        'is_verified',
        'primary_role',
        'last_activity',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'suspended_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ========== RELATIONS ==========

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'sent_by');
    }

    // ========== SCOPES ==========

    public function scopeWithoutGhost($query)
    {
        return $query->whereDoesntHave('roles', fn ($q) => $q->where('name', 'ghost'));
    }

    public function scopeActive($query)
    {
        return $query->whereNull('suspended_at');
    }

    public function scopeSuspended($query)
    {
        return $query->whereNotNull('suspended_at');
    }

    public function scopeSearch($query, $search)
    {
        if (empty($search)) return $query;
        
        $term = '%' . str_replace(' ', '%', $search) . '%';
        $operator = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';

        return $query->where(function ($q) use ($term, $operator) {
            $q->where('name', $operator, $term)
                ->orWhere('email', $operator, $term)
                ->orWhere('username', $operator, $term)
                ->orWhere('phone', $operator, $term);
        });
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    public function scopeUnverified($query)
    {
        return $query->whereNull('email_verified_at');
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeActiveRecently($query, $days = 7)
    {
        return $query->where('last_login_at', '>=', now()->subDays($days));
    }
    
    public function scopeStatus($query, $status)
    {
        return match ($status) {
            'active' => $query->active(),
            'suspended' => $query->suspended(),
            'verified' => $query->verified(),
            'unverified' => $query->unverified(),
            'inactive' => $query->where(function($q) {
                $q->where('last_login_at', '<', now()->subDays(90))
                  ->orWhereNull('last_login_at');
            }),
            default => $query,
        };
    }

    // ========== LOGIQUE MÉTIER ==========

    /**
     * Normalise le numéro via la librairie Propaganistas/LaravelPhone
     */
    public static function normalizePhone(?string $phone, string $countryCode = 'CI'): ?string
    {
        if (empty($phone)) return null;

        try {
            return (string) PhoneNumber::make($phone, $countryCode)->formatE164();
        } catch (\Exception $e) {
            return preg_replace('/[^0-9+]/', '', $phone);
        }
    }

    /**
     * Création centralisée via invitation
     */
    public static function createFromInvitation(array $data, Invitation $invitation): self
    {
        return DB::transaction(function () use ($data, $invitation) {
            
            $country = $data['country'] ?? 'CI';

            $user = self::create([
                'name' => trim($data['name']),
                'email' => strtolower(trim($invitation->email)),
                'password' => Hash::make($data['password']),
                'username' => !empty($data['username']) ? strtolower(trim($data['username'])) : null,
                'country' => $country, 
                'phone' => self::normalizePhone($data['phone'], $country),
                'email_verified_at' => now(),
                'last_login_at' => now(),
                'last_login_ip' => request()->ip(),
            ]);

            $user->assignRole($invitation->role);
            $invitation->markAsAccepted();
            event(new Registered($user));

            return $user;
        });
    }

    // ========== ACTIONS D'ÉTAT (NOUVEAU) ==========

    /**
     * Suspendre l'utilisateur
     */
    public function suspend(): void
    {
        if (!$this->canBeSuspended()) {
            throw new \LogicException("Action non autorisée sur cet utilisateur.");
        }
        $this->update(['suspended_at' => now()]);
    }

    /**
     * Réactiver l'utilisateur
     */
    public function unSuspend(): void
    {
        $this->update(['suspended_at' => null]);
    }

    // ========== SÉCURITÉ & DROITS ==========

    public function canBeModified(): bool
    {
        $authUser = auth()->user();
        if (!$authUser) return false;
        
        if ($authUser->id === $this->id) return false; // On peut se modifier soi-même (profil)
        if ($authUser->hasGhostRole()) return true;
        if ($this->hasGhostRole()) return false; // Personne ne modifie un Super Admin sauf lui-même
        if ($authUser->hasAdminRole()) return !$this->hasRole('admin');
        
        return false;
    }

    public function canBeDeleted(): bool
    {
        $authUser = auth()->user();
        if (!$authUser) return false;

        if ($authUser->id === $this->id) return false; // On ne peut pas se supprimer soi-même
        if ($this->hasGhostRole()) return false;
        if ($authUser->hasGhostRole()) return true;
        if ($authUser->hasAdminRole()) return !$this->hasRole('admin');

        return false;
    }

    public function canBeSuspended(): bool
    {
        $authUser = auth()->user();
        if (!$authUser) return false;

        if ($authUser->id === $this->id) return false; // On ne peut pas se suspendre soi-même
        return $this->canBeModified();
    }

    public function hasAdminRole(): bool 
    { 
        return $this->hasRole('admin'); 
    }

    public function hasGhostRole(): bool 
    { 
        return $this->hasRole('ghost'); 
    }

    // ========== UTILITAIRES ==========

    public function hasCustomAvatar(): bool
    {
        return !empty($this->avatar_url);
    }

    public function avatar(): string
    {
        if ($this->hasCustomAvatar()) {
            return Storage::url($this->avatar_url);
        }
        
        $name = urlencode($this->name);
        return "https://ui-avatars.com/api/?name={$name}&background=405189&color=fff&size=128&bold=true";
    }

    public function initials(): string
    {
        return Str::of($this->name)->explode(' ')->take(2)
            ->map(fn ($w) => Str::upper(Str::substr($w, 0, 1)))->implode('');
    }

    public function getUsername(): string
    {
        return $this->username ?? Str::before($this->email, '@');
    }

    public function isActive(): bool { return is_null($this->suspended_at); }
    public function isSuspended(): bool { return !is_null($this->suspended_at); }

    public function updateLastLogin(string $ipAddress): bool
    {
        return $this->update(['last_login_at' => now(), 'last_login_ip' => $ipAddress]);
    }

    // ========== ACCESSEURS ==========

    public function getFullNameWithStatusAttribute(): string
    {
        return $this->suspended_at ? "{$this->name} (Suspendu)" : $this->name;
    }

    public function getIsVerifiedAttribute(): bool { return !is_null($this->email_verified_at); }
    public function getPrimaryRoleAttribute(): ?string { return $this->roles->first()?->name; }

    public function getLastActivityAttribute(): ?string
    {
        return $this->last_login_at?->diffForHumans() ?? 'Jamais';
    }

    // 4. Configuration des options de log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'username', 'avatar_url']) // Champs à surveiller
            ->logOnlyDirty() // Ne log que ce qui a changé
            ->dontSubmitEmptyLogs() // N'enregistre rien si aucun changement
            ->setDescriptionForEvent(fn(string $eventName) => "Le compte a été {$eventName}");
    }
}