<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Reminder extends Model
{
    protected $fillable = [
        'user_id',
        'whatsapp_number_id',
        'google_event_id',
        'google_calendar_connection_id',
        'title',
        'description',
        'reminder_at',
        'event_at',
        'minutes_before',
        'location',
        'recurrence_rule',
        'status',
        'sent_at',
        'wa_message_id',
        'error_message',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'reminder_at' => 'datetime',
            'event_at' => 'datetime',
            'sent_at' => 'datetime',
            'minutes_before' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function whatsappNumber(): BelongsTo
    {
        return $this->belongsTo(WhatsappNumber::class);
    }

    public function googleCalendarConnection(): BelongsTo
    {
        return $this->belongsTo(GoogleCalendarConnection::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ReminderLog::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeDueNow(Builder $query): Builder
    {
        return $query->where('status', 'pending')
            ->where('reminder_at', '<=', now());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', 'pending')
            ->where('reminder_at', '>', now())
            ->orderBy('reminder_at');
    }
}
