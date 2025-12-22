<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\PromotionType;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'description', 'type', 'value',
        'minimum_purchase', 'max_uses', 'max_uses_per_customer',
        'current_uses', 'start_date', 'end_date',
        'applicable_products', 'applicable_categories',
        'is_active', 'combinable',
    ];

    protected $casts = [
        'type' => PromotionType::class,
        'value' => 'decimal:2',
        'minimum_purchase' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'applicable_products' => 'array',
        'applicable_categories' => 'array',
        'is_active' => 'boolean',
        'combinable' => 'boolean',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    // Méthodes
    public function isValid(): bool
    {
        if (!$this->is_active) return false;
        if (now()->lt($this->start_date) || now()->gt($this->end_date)) return false;
        if ($this->max_uses && $this->current_uses >= $this->max_uses) return false;
        
        return true;
    }

    public function canBeUsedBy(Customer $customer): bool
    {
        if (!$this->isValid()) return false;
        
        if ($this->max_uses_per_customer) {
            $customerUses = Order::where('customer_id', $customer->id)
                ->where('discount_code', $this->code)
                ->count();
            
            if ($customerUses >= $this->max_uses_per_customer) return false;
        }
        
        return true;
    }

    public function calculateDiscount(float $subtotal): float
    {
        if ($this->minimum_purchase && $subtotal < $this->minimum_purchase) {
            return 0;
        }

        return match($this->type) {
            PromotionType::PERCENTAGE => $subtotal * ($this->value / 100),
            PromotionType::FIXED_AMOUNT => min($this->value, $subtotal),
            default => 0,
        };
    }

    public function incrementUses(): void
    {
        // CORRECTION : Incrémentation atomique (plus sûr que read-modify-write)
        $this->increment('current_uses');
    }
}