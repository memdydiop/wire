<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\ShiftStatus;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'shift_date',
        'start_time',
        'end_time',
        'actual_start_time',
        'actual_end_time',
        'status',
        'role',
        'break_duration',
        'notes',
    ];

    protected $casts = [
        'shift_date' => 'date',
        'status' => ShiftStatus::class,
        'break_duration' => 'decimal:2',
    ];

    // Relations
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // Scopes
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('shift_date', $date);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', ShiftStatus::COMPLETED);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', ShiftStatus::SCHEDULED);
    }

    // Méthodes
    public function clockIn(): void
    {
        $this->actual_start_time = now()->format('H:i:s');
        $this->status = ShiftStatus::CONFIRMED;
        $this->save();
    }

    public function clockOut(): void
    {
        $this->actual_end_time = now()->format('H:i:s');
        $this->status = ShiftStatus::COMPLETED;
        $this->save();
    }

    public function getDuration(): float
    {
        if (!$this->actual_start_time || !$this->actual_end_time) {
            $start = \Carbon\Carbon::parse($this->start_time);
            $end = \Carbon\Carbon::parse($this->end_time);
        } else {
            $start = \Carbon\Carbon::parse($this->actual_start_time);
            $end = \Carbon\Carbon::parse($this->actual_end_time);
        }
        
        return $end->diffInHours($start) - $this->break_duration;
    }
}