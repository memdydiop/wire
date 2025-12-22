<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\InventoryMovementType;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'ingredient_id',
        'type',
        'quantity',
        'unit',
        'unit_cost',
        'total_cost',
        'reference',
        'supplier_id',
        'production_batch_id',
        'order_id',
        'user_id',
        'reason',
        'expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'type' => InventoryMovementType::class,
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'expiry_date' => 'date',
        ];
    }

    // Relations
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function productionBatch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeIn($query)
    {
        return $query->where('type', InventoryMovementType::IN);
    }

    public function scopeOut($query)
    {
        return $query->where('type', InventoryMovementType::OUT);
    }

    public function scopeForIngredient($query, $ingredientId)
    {
        return $query->where('ingredient_id', $ingredientId);
    }

    // Événements
    protected static function booted(): void
    {
        static::created(function (InventoryMovement $movement) {
            $ingredient = $movement->ingredient;
            
            // Note: Assurez-vous que votre Enum InventoryMovementType implémente bien la méthode isPositive()
            if ($movement->type->isPositive()) {
                $ingredient->increment('quantity_in_stock', $movement->quantity);
            } else {
                $ingredient->decrement('quantity_in_stock', $movement->quantity);
            }
        });
    }
}