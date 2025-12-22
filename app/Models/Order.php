<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\FulfillmentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'customer_id',
        'user_id',
        'status',
        'type',
        'fulfillment_method',
        'subtotal',
        'discount_amount',
        'discount_code',
        'tax_amount',
        'delivery_fee',
        'total',
        'paid_amount',
        'payment_status',
        'payment_method',
        'pickup_date',
        'pickup_time',
        'delivery_address',
        'delivery_city',
        'delivery_postal_code',
        'customer_notes',
        'internal_notes',
        'requires_personalization',
        'personalization_details',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'type' => OrderType::class,
            'fulfillment_method' => FulfillmentMethod::class,
            'payment_status' => PaymentStatus::class,
            'payment_method' => PaymentMethod::class,
            'pickup_date' => 'datetime',
            'personalization_details' => 'array',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'requires_personalization' => 'boolean',
        ];
    }

    // Relations
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', OrderStatus::PENDING);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', OrderStatus::CONFIRMED);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('pickup_date', $date);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', PaymentStatus::UNPAID);
    }

    // Méthodes
    public function canTransitionTo(OrderStatus $newStatus): bool
    {
        return $this->status->canTransitionTo($newStatus);
    }

    public function updateStatus(OrderStatus $newStatus): bool
    {
        if (!$this->canTransitionTo($newStatus)) {
            return false;
        }
        $this->status = $newStatus;
        return $this->save();
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::PAID;
    }

    public function getRemainingAmount(): float
    {
        return (float) ($this->total - $this->paid_amount);
    }

    public function calculateTotal(): void
    {
        $this->total = $this->subtotal - $this->discount_amount + $this->tax_amount + $this->delivery_fee;
        $this->save();
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
        });
    }

    protected static function generateOrderNumber(): string
    {
        $prefix = 'CMD';
        $date = now()->format('Ymd');
        
        // Ajout de withTrashed() pour inclure les commandes supprimées dans le calcul de la séquence
        // afin d'éviter les collisions de numéros de commande.
        $lastOrder = self::withTrashed()
                         ->whereDate('created_at', today())
                         ->latest('id')
                         ->first();
                         
        $sequence = $lastOrder ? (int) substr($lastOrder->order_number, -4) + 1 : 1;
        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}