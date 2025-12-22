<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RecipeIngredient extends Pivot
{
    use HasFactory;

    protected $table = 'recipe_ingredients';
    
    public $incrementing = true;

    protected $fillable = [
        'recipe_id',
        'ingredient_id',
        'quantity',
        'unit',
        'cost',
        'order',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'cost' => 'decimal:2',
    ];

    // Relations
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    // Méthodes
    public function calculateCost(): void
    {
        $ingredient = $this->ingredient;
        
        if ($ingredient) {
            $this->cost = $this->quantity * $ingredient->unit_price;
            $this->save();
        }
    }

    public function updateRecipeTotalCost(): void
    {
        $this->recipe->updateTotalCost();
    }

    // Événements
    protected static function booted(): void
    {
        static::saving(function (RecipeIngredient $recipeIngredient) {
            // Calculer automatiquement le coût si l'ingrédient est chargé
            if ($recipeIngredient->ingredient && !$recipeIngredient->cost) {
                $recipeIngredient->cost = $recipeIngredient->quantity * $recipeIngredient->ingredient->unit_price;
            }
        });

        static::saved(function (RecipeIngredient $recipeIngredient) {
            // Mettre à jour le coût total de la recette
            $recipeIngredient->recipe->updateTotalCost();
        });

        static::deleted(function (RecipeIngredient $recipeIngredient) {
            // Mettre à jour le coût total de la recette après suppression
            $recipeIngredient->recipe->updateTotalCost();
        });
    }

    // Scopes
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    // Accesseurs
    public function getFormattedQuantityAttribute(): string
    {
        return number_format($this->quantity, 2) . ' ' . $this->unit;
    }

    public function getFormattedCostAttribute(): string
    {
        return number_format($this->cost, 2) . ' €';
    }
}