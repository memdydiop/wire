<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\InvoiceType;
use App\Enums\InvoiceStatus;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'order_id',
        'invoice_date',
        'due_date',
        'type',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'notes',
        'pdf_path',
        'sent_at',
        'paid_at',
    ];

    protected $casts = [
        'type' => InvoiceType::class,
        'status' => InvoiceStatus::class,
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
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
    public function scopeDraft($query)
    {
        return $query->where('status', InvoiceStatus::DRAFT);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', InvoiceStatus::SENT)
            ->where('due_date', '<', now());
    }

    public function scopePaid($query)
    {
        return $query->where('status', InvoiceStatus::PAID);
    }

    // Méthodes
    public function markAsSent(): void
    {
        $this->status = InvoiceStatus::SENT;
        $this->sent_at = now();
        $this->save();
    }

    public function markAsPaid(): void
    {
        $this->status = InvoiceStatus::PAID;
        $this->paid_at = now();
        $this->save();
    }

    public function isOverdue(): bool
    {
        return $this->status === InvoiceStatus::SENT 
            && $this->due_date 
            && $this->due_date->isPast();
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber($invoice->type);
            }
        });
    }

    protected static function generateInvoiceNumber(InvoiceType $type): string
    {
        $prefix = $type->prefix();
        $year = now()->format('Y');
        $lastInvoice = self::where('type', $type)
            ->whereYear('created_at', now()->year)
            ->latest('id')
            ->first();
        
        $sequence = $lastInvoice ? (int) substr($lastInvoice->invoice_number, -4) + 1 : 1;
        return sprintf('%s-%s-%04d', $prefix, $year, $sequence);
    }
}