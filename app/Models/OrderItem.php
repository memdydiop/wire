<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'product_name',
        'variant_name',
        'quantity',
        'unit_price',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'total',
        'customization',
        'special_instructions',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'customization' => 'array',
        ];
    }

    // Relations
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        // withTrashed() permet de conserver l'accès aux données produit 
        // dans l'historique de commande même si le produit est supprimé du catalogue.
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    // Méthodes
    public function calculateTotal(): void
    {
        $quantity = (int) $this->quantity;
        $unitPrice = (float) $this->unit_price;
        $discount = (float) $this->discount_amount;
        $taxRate = (float) $this->tax_rate;

        $subtotal = $quantity * $unitPrice;
        $afterDiscount = max(0, $subtotal - $discount);
        
        $this->tax_amount = $afterDiscount * ($taxRate / 100);
        $this->total = $afterDiscount + $this->tax_amount;
        
        $this->save();
    }
}