<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\ContractType;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'employee_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'position',
        'contract_type',
        'hire_date',
        'end_date',
        'hourly_rate',
        'monthly_salary',
        'weekly_hours',
        'skills',
        'certifications',
        'is_active',
    ];

    protected $casts = [
        'contract_type' => ContractType::class,
        'hire_date' => 'date',
        'end_date' => 'date',
        'hourly_rate' => 'decimal:2',
        'monthly_salary' => 'decimal:2',
        'skills' => 'array',
        'certifications' => 'array',
        'is_active' => 'boolean',
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function productionBatches(): HasMany
    {
        return $this->hasMany(ProductionBatch::class, 'user_id', 'user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFullTime($query)
    {
        return $query->where('contract_type', ContractType::FULL_TIME);
    }

    // Méthodes
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function hasSkill(string $skill): bool
    {
        return in_array($skill, $this->skills ?? []);
    }

    public function getWorkedHoursForPeriod($startDate, $endDate): float
    {
        return (float) $this->shifts()
            ->whereBetween('shift_date', [$startDate, $endDate])
            ->where('status', \App\Enums\ShiftStatus::COMPLETED)
            ->get()
            ->sum(function ($shift) {
                if (!$shift->actual_start_time || !$shift->actual_end_time) return 0;
                
                $start = \Carbon\Carbon::parse($shift->actual_start_time);
                $end = \Carbon\Carbon::parse($shift->actual_end_time);
                
                return $end->diffInHours($start) - $shift->break_duration;
            });
    }
}