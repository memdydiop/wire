<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use App\Enums\CustomerType;
use App\Enums\LoyaltyTransactionType;
use App\Enums\PaymentStatus;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'first_name', 'last_name', 'email', 'phone',
        'address', 'address_complement', 'city', 'postal_code', 'country',
        'birth_date', 'preferences', 'allergens', 'notes',
        'loyalty_points', 'customer_type', 'accepts_marketing', 'is_active',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'preferences' => 'array',
        'allergens' => 'array',
        'customer_type' => CustomerType::class,
        'accepts_marketing' => 'boolean',
        'is_active' => 'boolean',
        'loyalty_points' => 'integer',
    ];

    // Relations
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function orders(): HasMany { return $this->hasMany(Order::class); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class); }
    public function loyaltyTransactions(): HasMany { return $this->hasMany(LoyaltyTransaction::class); }
    public function reviews(): HasMany { return $this->hasMany(Review::class); }
    public function notifications(): HasMany { return $this->hasMany(Notification::class); }

    // Scopes
    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeVip($query) { return $query->where('customer_type', CustomerType::VIP); }
    public function scopeAcceptsMarketing($query) { return $query->where('accepts_marketing', true); }

    // Méthodes
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // CORRECTION : Transaction atomique pour l'ajout de points
    public function addLoyaltyPoints(int $points, ?Order $order = null, string $description = null): void
    {
        DB::transaction(function () use ($points, $order, $description) {
            // Incrémentation directe en base de données
            $this->increment('loyalty_points', $points);

            $this->loyaltyTransactions()->create([
                'order_id' => $order?->id,
                'type' => LoyaltyTransactionType::EARNED,
                'points' => $points,
                'balance_after' => $this->refresh()->loyalty_points, // Récupère la valeur réelle
                'description' => $description ?? "Points gagnés",
            ]);
        });
    }

    // CORRECTION : Verrouillage pessimiste pour le retrait de points
    public function redeemLoyaltyPoints(int $points, ?Order $order = null, string $description = null): bool
    {
        return DB::transaction(function () use ($points, $order, $description) {
            // Verrouille la ligne client pour lecture/écriture exclusive
            $customer = static::lockForUpdate()->find($this->id);

            if ($customer->loyalty_points < $points) {
                return false;
            }

            $customer->decrement('loyalty_points', $points);

            $customer->loyaltyTransactions()->create([
                'order_id' => $order?->id,
                'type' => LoyaltyTransactionType::REDEEMED,
                'points' => -$points,
                'balance_after' => $customer->loyalty_points,
                'description' => $description ?? "Points utilisés",
            ]);

            return true;
        });
    }

    public function getTotalSpent(): float
    {
        return (float) $this->orders()
            ->where('payment_status', PaymentStatus::PAID)
            ->sum('total');
    }

    public function getOrderCount(): int
    {
        return $this->orders()->count();
    }
}