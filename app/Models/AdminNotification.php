<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    protected $fillable = [
        'notification_type',
        'user_id',
        'data',
        'sent_at',
        'failed_attempts',
        'last_error',
    ];

    protected $casts = [
        'data' => 'array',
        'sent_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->whereNull('sent_at')
                     ->where('failed_attempts', '<', 3); // Max 3 retry attempts
    }

    public function scopeFailed($query)
    {
        return $query->whereNull('sent_at')
                     ->where('failed_attempts', '>=', 3);
    }

    public function markAsSent()
    {
        $this->update(['sent_at' => now()]);
    }

    public function incrementFailure($error = null)
    {
        $this->increment('failed_attempts');
        if ($error) {
            $this->update(['last_error' => $error]);
        }
    }
}
