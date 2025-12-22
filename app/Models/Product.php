<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\StorageType;
use App\Enums\DifficultyLevel;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    // Constante pour configuration facile
    const LOW_STOCK_THRESHOLD = 5;

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'description',
        'short_description',
        'category_id',
        'base_price',
        'cost_price',
        'selling_price',
        'margin_percentage',
        'vat_rate',
        'preparation_time',
        'cooking_time',
        'shelf_life',
        'storage_type',
        'allergens',
        'nutritional_info',
        'portions',
        'difficulty_level',
        'is_seasonal',
        'is_featured',
        'is_available',
        'requires_advance_order',
        'advance_order_days',
        'daily_production_limit',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'margin_percentage' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'storage_type' => StorageType::class,
        'difficulty_level' => DifficultyLevel::class,
        'allergens' => 'array',
        'nutritional_info' => 'array',
        'is_seasonal' => 'boolean',
        'is_featured' => 'boolean',
        'is_available' => 'boolean',
        'requires_advance_order' => 'boolean',
    ];

    // Relations
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function productionBatches(): HasMany
    {
        return $this->hasMany(ProductionBatch::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeSeasonal($query)
    {
        return $query->where('is_seasonal', true);
    }

    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // Méthodes
    public function getPrimaryImage(): ?ProductImage
    {
        return $this->images()->where('is_primary', true)->first();
    }

    public function calculateMargin(): float
    {
        if ($this->selling_price == 0) return 0; // Sécurité anti-division par zéro
        if ($this->cost_price == 0) return 100; // Marge de 100% si coût nul
        
        return (float) (($this->selling_price - $this->cost_price) / $this->selling_price) * 100;
    }

    public function updateMargin(): void
    {
        $this->margin_percentage = $this->calculateMargin();
        $this->save();
    }

    public function getPriceWithTax(): float
    {
        return (float) ($this->selling_price * (1 + ($this->vat_rate / 100)));
    }

    public function hasVariants(): bool
    {
        return $this->variants()->exists();
    }

    public function getAverageRating(): float
    {
        return (float) $this->reviews()->where('is_approved', true)->avg('rating') ?? 0.0;
    }

    public function getTotalReviews(): int
    {
        return $this->reviews()->where('is_approved', true)->count();
    }
    
    public function getStockStatusAttribute(): string
    {
        $totalAvailable = $this->productionBatches()->sum('quantity_available');
        
        if ($totalAvailable == 0) {
            return 'out_of_stock';
        }
        // Utilisation de la constante au lieu du chiffre magique '5'
        if ($totalAvailable <= self::LOW_STOCK_THRESHOLD) { 
            return 'low_stock';
        }
        return 'in_stock';
    }
    
    public function getAvailableQuantityAttribute(): int
    {
        return (int) $this->productionBatches()->sum('quantity_available');
    }
}