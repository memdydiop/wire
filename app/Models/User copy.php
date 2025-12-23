<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, HasRoles,  Notifiable, TwoFactorAuthenticatable;

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

    /**
     * Invitations envoyées par cet utilisateur
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'sent_by');
    }

    // ========== SCOPES ==========

    public function scopeWithoutGhost($query)
    {
        return $query->whereDoesntHave('roles', fn ($query) => $query->where('name', 'ghost'));
    }

    public function scopeActive($query)
    {
        return $query->whereNull('suspended_at');
    }

    public function scopeSuspended($query)
    {
        return $query->whereNotNull('suspended_at');
    }

    /**
     * Scope pour recherche globale
     */
    public function scopeSearch($query, $search)
    {
        if (empty($search)) {
            return $query;
        }

        $search = '%' . str_replace(' ', '%', $search) . '%';
        
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'ilike', $search)
                ->orWhere('email', 'ilike', $search)
                ->orWhere('username', 'ilike', $search)
                ->orWhere('phone', 'ilike', $search)
                ->orWhereHas('roles', function ($roleQuery) use ($search) {
                    $roleQuery->where('name', 'ilike', $search);
                });
        });
    }

    /**
     * Scope pour filtrer par vérification email
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    public function scopeUnverified($query)
    {
        return $query->whereNull('email_verified_at');
    }

    /**
     * Scope pour récupérer les utilisateurs récents
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope pour les utilisateurs actifs récemment
     */
    public function scopeActiveRecently($query, $days = 7)
    {
        return $query->where('last_login_at', '>=', now()->subDays($days));
    }

    /**
     * Scope unifié pour le filtrage par statut
     */
    public function scopeStatus($query, $status)
    {
        return match ($status) {
            'active' => $query->active(),
            'suspended' => $query->suspended(),
            'verified' => $query->verified(),
            'unverified' => $query->unverified(),
            'inactive' => $query->where('last_login_at', '<', now()->subDays(90))
                            ->orWhereNull('last_login_at'),
            default => $query,
        };
    }

    /**
     * Scope pour filtrer par rôle
     */
    public function scopeByRole($query, $role)
    {
        if (empty($role)) {
            return $query;
        }
        
        return $query->whereHas('roles', function ($q) use ($role) {
            $q->where('name', $role);
        });
    }

    // ========== MÉTHODES UTILITAIRES ==========

    public function initials(): string
    {
        if (empty($this->name)) {
            return '??';
        }
        
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');
    }

    /**
     * Obtenir le nom d'utilisateur
     */
    public function getUsername(): string
    {
        return $this->username ?? Str::before($this->email, '@');
    }

    public function avatar(): string
    {
        if ($this->avatar_url) {
            return $this->avatar_url;
        }
        
        // Générer un avatar basé sur les initiales
        $initials = $this->initials();
        $colors = ['#3B82F6', '#10B981', '#8B5CF6', '#EF4444', '#F59E0B'];
        $color = $colors[crc32($this->email) % count($colors)];
        
        return "https://ui-avatars.com/api/?name={$initials}&background={$color}&color=fff&size=128";
    }

    public function hasCustomAvatar(): bool
    {
        return !empty($this->avatar_url);
    }

    public function isSuspended(): bool
    {
        return !is_null($this->suspended_at);
    }

    public function isActive(): bool
    {
        return is_null($this->suspended_at);
    }

    /**
     * Suspendre un utilisateur
     */
    public function suspend(string $reason = null): bool
    {
        if ($this->hasAdminRole()) {
            throw new \LogicException('Impossible de suspendre un administrateur');
        }

        if ($this->id === Auth::id()) {
            throw new \LogicException('Impossible de se suspendre soi-même');
        }

        if ($this->isSuspended()) {
            throw new \LogicException('Cet utilisateur est déjà suspendu');
        }

        $result = $this->update(['suspended_at' => now()]);
        
        if ($result) {
            Log::warning('Utilisateur suspendu', [
                'user_id' => $this->id,
                'suspended_by' => Auth::id(),
                'reason' => $reason,
            ]);
        }
        
        return $result;
    }

    /**
     * Réactiver un utilisateur
     */
    public function unSuspend(): bool
    {
        if (!$this->isSuspended()) {
            throw new \LogicException('Cet utilisateur n\'est pas suspendu');
        }

        $result = $this->update(['suspended_at' => null]);
        
        if ($result) {
            Log::info('Utilisateur réactivé', [
                'user_id' => $this->id,
                'reactivated_by' => Auth::id(),
            ]);
        }
        
        return $result;
    }

    public function updateLastLogin(string $ipAddress): bool
    {
        return $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
        ]);
    }

    public function displayName(): string
    {
        return $this->username ?? $this->name;
    }

    public function hasAdminRole(): bool
    {
        return $this->hasAnyRole(['super-admin', 'admin', 'ghost']);
    }

    public function hasSuperAdminRole(): bool
    {
        return $this->hasRole('super-admin');
    }

    // ========== ACCESSEURS ==========

    /**
     * Obtenir le nom complet formaté avec le statut
     */
    public function getFullNameWithStatusAttribute(): string
    {
        $name = $this->name;

        if ($this->isSuspended()) {
            $name .= ' (Suspendu)';
        }

        return $name;
    }

    /**
     * Vérifier si l'utilisateur est vérifié
     */
    public function getIsVerifiedAttribute(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Obtenir le rôle principal
     */
    public function getPrimaryRoleAttribute(): ?string
    {
        return $this->roles->first()?->name;
    }

    /**
     * Obtenir la date de dernière activité
     */
    public function getLastActivityAttribute(): ?string
    {
        return $this->last_login_at?->diffForHumans() ?? 'Jamais';
    }

    /**
     * Obtenir le statut de compte
     */
    public function getAccountStatusAttribute(): string
    {
        if ($this->isSuspended()) {
            return 'suspended';
        }
        
        if (!$this->isVerified()) {
            return 'unverified';
        }
        
        if ($this->isInactive()) {
            return 'inactive';
        }
        
        return 'active';
    }

    /**
     * Obtenir le libellé du statut
     */
    public function getAccountStatusLabelAttribute(): string
    {
        return match($this->account_status) {
            'suspended' => 'Suspendu',
            'unverified' => 'Non vérifié',
            'inactive' => 'Inactif',
            'active' => 'Actif',
            default => 'Inconnu',
        };
    }

    // ========== MÉTHODES DE VÉRIFICATION ==========

    /**
     * Vérifier si l'utilisateur peut être modifié
     */
    public function canBeModified(): bool
    {
        return $this->id !== Auth::id() 
            && !$this->hasRole('ghost')
            && !$this->hasSuperAdminRole();
    }

    /**
     * Vérifier si l'utilisateur peut être supprimé
     */
    public function canBeDeleted(): bool
    {
        return $this->id !== Auth::id()
            && !$this->hasAdminRole()
            && !$this->hasRole('ghost');
    }

    /**
     * Vérifier si l'utilisateur peut être suspendu
     */
    public function canBeSuspended(): bool
    {
        return $this->id !== Auth::id()
            && !$this->hasAdminRole()
            && $this->isActive();
    }

    /**
     * Vérifier si l'utilisateur peut être réactivé
     */
    public function canBeUnsuspended(): bool
    {
        return $this->isSuspended();
    }

    // ========== MÉTHODES STATISTIQUES ==========

    /**
     * Nombre de jours depuis l'inscription
     */
    public function getDaysSinceRegistration(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Nombre de jours depuis la dernière connexion
     */
    public function getDaysSinceLastLogin(): ?int
    {
        return $this->last_login_at?->diffInDays(now());
    }

    /**
     * Vérifier si l'utilisateur est inactif
     */
    public function isInactive(int $days = 90): bool
    {
        if (!$this->last_login_at) {
            return $this->created_at->addDays($days) < now();
        }

        return $this->last_login_at->addDays($days) < now();
    }

    // ========== MÉTHODES DE REQUÊTE STATIQUES ==========

    /**
     * Obtenir les statistiques des utilisateurs
     */
    public static function getStats(): array
    {
        return [
            'total' => static::count(),
            'active' => static::active()->count(),
            'suspended' => static::suspended()->count(),
            'verified' => static::verified()->count(),
            'unverified' => static::unverified()->count(),
            'recent' => static::recent(30)->count(),
            'active_recently' => static::activeRecently(7)->count(),
            'inactive' => static::status('inactive')->count(),
        ];
    }

    /**
     * Obtenir les utilisateurs à risque
     */
    public static function getInactiveUsers(int $days = 90)
    {
        return static::where(function ($query) use ($days) {
                $query->where('last_login_at', '<', now()->subDays($days))
                    ->orWhereNull('last_login_at');
            })
            ->active()
            ->verified()
            ->get();
    }

    /**
     * Obtenir les statistiques par rôle
     */
    public static function getStatsByRole(): array
    {
        $roles = \Spatie\Permission\Models\Role::all();
        $stats = [];
        
        foreach ($roles as $role) {
            $stats[$role->name] = static::whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role->name);
            })->count();
        }
        
        return $stats;
    }

    /**
     * Créer un utilisateur via invitation
     */
    public static function createFromInvitation(array $data, Invitation $invitation): self
    {
        return DB::transaction(function () use ($data, $invitation) {
            $user = self::create([
                'name' => trim($data['name']),
                'email' => strtolower(trim($invitation->email)),
                'password' => Hash::make($data['password']),
                'username' => isset($data['username']) ? strtolower(trim($data['username'])) : null,
                'phone' => isset($data['phone']) ? preg_replace('/[^0-9+]/', '', $data['phone']) : null,
                'email_verified_at' => now(),
                'last_login_at' => now(),
                'last_login_ip' => request()->ip(),
            ]);

            $user->assignRole($invitation->role);
            $invitation->markAsAccepted();

            return $user;
        });
    }
}