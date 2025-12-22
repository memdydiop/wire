<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\StockAlertType;
use App\Enums\AlertStatus;

class StockAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'ingredient_id',
        'alert_type',
        'status',
        'current_quantity',
        'threshold_quantity',
        'expiry_date',
        'acknowledged_by',
        'acknowledged_at',
    ];

    protected $casts = [
        'alert_type' => StockAlertType::class,
        'status' => AlertStatus::class,
        'current_quantity' => 'decimal:3',
        'threshold_quantity' => 'decimal:3',
        'expiry_date' => 'date',
        'acknowledged_at' => 'datetime',
    ];

    // Relations
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', AlertStatus::ACTIVE);
    }

    public function scopeCritical($query)
    {
        return $query->whereIn('alert_type', [
            StockAlertType::OUT_OF_STOCK,
            StockAlertType::EXPIRED
        ]);
    }

    // Méthodes
    public function acknowledge(User $user): void
    {
        $this->status = AlertStatus::ACKNOWLEDGED;
        $this->acknowledged_by = $user->id;
        $this->acknowledged_at = now();
        $this->save();
    }

    public function resolve(): void
    {
        $this->status = AlertStatus::RESOLVED;
        $this->save();
    }
}