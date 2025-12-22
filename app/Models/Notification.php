<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\NotificationType;
use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_id',
        'type',
        'title',
        'message',
        'data',
        'channel',
        'status',
        'sent_at',
        'read_at',
    ];

    protected $casts = [
        'type' => NotificationType::class,
        'channel' => NotificationChannel::class,
        'status' => NotificationStatus::class,
        'data' => 'array',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeSent($query)
    {
        return $query->where('status', NotificationStatus::SENT);
    }

    public function scopePending($query)
    {
        return $query->where('status', NotificationStatus::PENDING);
    }

    // Méthodes
    public function markAsRead(): void
    {
        $this->read_at = now();
        $this->status = NotificationStatus::READ;
        $this->save();
    }

    public function markAsSent(): void
    {
        $this->sent_at = now();
        $this->status = NotificationStatus::SENT;
        $this->save();
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}