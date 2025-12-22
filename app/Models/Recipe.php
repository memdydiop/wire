<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'variant_id',
        'name',
        'instructions',
        'yield_quantity',
        'total_cost',
        'version',
        'is_active',
    ];

    protected $casts = [
        'total_cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relations
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredients')
            ->withPivot('quantity', 'unit', 'cost', 'order', 'notes')
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function productionBatches(): HasMany
    {
        return $this->hasMany(ProductionBatch::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Méthodes
    public function calculateTotalCost(): float
    {
        return (float) $this->ingredients->sum(function ($ingredient) {
            return $ingredient->pivot->cost;
        });
    }

    public function updateTotalCost(): void
    {
        $this->total_cost = $this->calculateTotalCost();
        $this->save();
    }

    public function getCostPerUnit(): float
    {
        return $this->yield_quantity > 0 ? ($this->total_cost / $this->yield_quantity) : 0;
    }
}