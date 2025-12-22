<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\ProductionStatus;

class ProductionBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_number',
        'production_date',
        'product_id',
        'recipe_id',
        'user_id',
        'planned_quantity',
        'produced_quantity',
        'defective_quantity',
        'status',
        'started_at',
        'completed_at',
        'production_cost',
        'notes',
        'quality_checks',
    ];

    protected $casts = [
        'production_date' => 'date',
        'status' => ProductionStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'production_cost' => 'decimal:2',
        'quality_checks' => 'array',
    ];

    // Relations
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    // Scopes
    public function scopePlanned($query)
    {
        return $query->where('status', ProductionStatus::PLANNED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', ProductionStatus::IN_PROGRESS);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('production_date', $date);
    }

    // Méthodes
    public function start(): void
    {
        $this->status = ProductionStatus::IN_PROGRESS;
        $this->started_at = now();
        $this->save();
    }

    public function complete(): void
    {
        $this->status = ProductionStatus::COMPLETED;
        $this->completed_at = now();
        $this->save();
    }

    public function getEfficiencyRate(): float
    {
        if ($this->planned_quantity == 0) return 0;
        return ($this->produced_quantity / $this->planned_quantity) * 100;
    }

    protected static function booted(): void
    {
        static::creating(function (ProductionBatch $batch) {
            if (empty($batch->batch_number)) {
                $batch->batch_number = self::generateBatchNumber();
            }
        });
    }

    protected static function generateBatchNumber(): string
    {
        $prefix = 'PROD';
        $date = now()->format('Ymd');
        $lastBatch = self::whereDate('created_at', today())->latest('id')->first();
        $sequence = $lastBatch ? (int) substr($lastBatch->batch_number, -4) + 1 : 1;
        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}