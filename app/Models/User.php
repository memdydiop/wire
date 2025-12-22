<?php

namespace App\Models;

use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;
use Propaganistas\LaravelPhone\PhoneNumber;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

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

    public static function normalizePhone(?string $phone, string $countryCode = 'CI'): ?string
    {
        if (empty($phone)) return null;

        try {
            return (string) PhoneNumber::make($phone, $countryCode)->formatE164();
        } catch (\Exception $e) {
            // Fallback si le parsing échoue
            return preg_replace('/[^0-9+]/', '', $phone);
        }
    }

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

    // ========== SÉCURITÉ & DROITS ==========

    public function canBeModified(): bool
    {
        $auth = auth()->user();
        if (!$auth) return false;
        
        if ($auth->id === $this->id) return $this->isActive();
        if ($auth->hasRole('super-admin')) return true;
        if ($this->hasRole('super-admin')) return false;
        if ($auth->hasRole('admin')) return !$this->hasRole('admin');
        
        return false;
    }

    public function canBeDeleted(): bool
    {
        $auth = auth()->user();
        if (!$auth) return false;

        if ($auth->id === $this->id) return false;
        if ($this->hasRole('super-admin')) return false;
        if ($auth->hasRole('super-admin')) return true;
        if ($auth->hasRole('admin')) return !$this->hasRole('admin');

        return false;
    }

    public function hasAdminRole(): bool 
    { 
        return $this->hasAnyRole(['super-admin', 'admin', 'ghost']); 
    }

    public function hasSuperAdminRole(): bool 
    { 
        return $this->hasRole('super-admin'); 
    }

    // ========== UTILITAIRES ==========

    public function hasCustomAvatar(): bool
    {
        return !empty($this->avatar_url);
    }

    public function avatar(): string
    {
        if ($this->hasCustomAvatar()) return $this->avatar_url;
        
        $name = urlencode($this->name);
        return "https://ui-avatars.com/api/?name={$name}&background=random&color=fff&size=128";
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
}