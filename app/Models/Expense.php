<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Enums\PaymentMethod;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'supplier_id',
        'category',
        'description',
        'amount',
        'vat_rate',
        'vat_amount',
        'total_amount',
        'expense_date',
        'payment_method',
        'status',
        'invoice_number',
        'document_path',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'category' => ExpenseCategory::class,
        'status' => ExpenseStatus::class,
        'payment_method' => PaymentMethod::class,
        'amount' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'expense_date' => 'date',
        'approved_at' => 'datetime',
    ];

    // Relations
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', ExpenseStatus::PENDING);
    }

    public function scopePaid($query)
    {
        return $query->where('status', ExpenseStatus::PAID);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }

    public function scopeByCategory($query, ExpenseCategory $category)
    {
        return $query->where('category', $category);
    }

    // Méthodes
    public function approve(User $user): void
    {
        $this->approved_by = $user->id;
        $this->approved_at = now();
        $this->save();
    }

    public function markAsPaid(): void
    {
        $this->status = ExpenseStatus::PAID;
        $this->save();
    }

    public function calculateVat(): void
    {
        $this->vat_amount = $this->amount * ($this->vat_rate / 100);
        $this->total_amount = $this->amount + $this->vat_amount;
        $this->save();
    }

    protected static function booted(): void
    {
        static::creating(function (Expense $expense) {
            if (empty($expense->reference)) {
                $expense->reference = self::generateReference();
            }
        });
    }

    protected static function generateReference(): string
    {
        $prefix = 'EXP';
        $date = now()->format('Ymd');
        $lastExpense = self::whereDate('created_at', today())->latest('id')->first();
        $sequence = $lastExpense ? (int) substr($lastExpense->reference, -4) + 1 : 1;
        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}