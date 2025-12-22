<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ingredient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'reference',
        'description',
        'supplier_id',
        'unit',
        'unit_price',
        'quantity_in_stock',
        'minimum_stock',
        'optimal_stock',
        'expiry_date',
        'storage_location',
        'vat_rate',
        'is_allergenic',
        'allergens',
        'is_active',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity_in_stock' => 'decimal:3',
        'minimum_stock' => 'decimal:3',
        'optimal_stock' => 'decimal:3',
        'vat_rate' => 'decimal:2',
        'is_allergenic' => 'boolean',
        'is_active' => 'boolean',
        'allergens' => 'array',
        'expiry_date' => 'date',
    ];

    // Relations
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'recipe_ingredients')
            ->withPivot('quantity', 'unit', 'cost', 'order', 'notes')
            ->withTimestamps();
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function stockAlerts(): HasMany
    {
        return $this->hasMany(StockAlert::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity_in_stock', '<=', 'minimum_stock');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('quantity_in_stock', 0);
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    // Méthodes
    public function isLowStock(): bool
    {
        return $this->quantity_in_stock <= $this->minimum_stock;
    }

    public function isOutOfStock(): bool
    {
        return $this->quantity_in_stock == 0;
    }

    public function isExpiringSoon(int $days = 7): bool
    {
        return $this->expiry_date && $this->expiry_date->between(now(), now()->addDays($days));
    }

    public function getTotalValue(): float
    {
        return (float) ($this->quantity_in_stock * $this->unit_price);
    }
    
    public function getStockStatusAttribute(): string
    {
        if ($this->isOutOfStock()) {
            return 'out_of_stock';
        }
        if ($this->isLowStock()) {
            return 'low_stock';
        }
        return 'in_stock';
    }
}