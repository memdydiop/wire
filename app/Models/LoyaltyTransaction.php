<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\LoyaltyTransactionType;

class LoyaltyTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'order_id',
        'type',
        'points',
        'balance_after',
        'description',
    ];

    protected $casts = [
        'type' => LoyaltyTransactionType::class,
    ];

    // Relations
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Scopes
    public function scopeEarned($query)
    {
        return $query->where('type', LoyaltyTransactionType::EARNED);
    }

    public function scopeRedeemed($query)
    {
        return $query->where('type', LoyaltyTransactionType::REDEEMED);
    }
}