<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'whatsapp_number_id',
        'contact_phone',
        'contact_jid',
        'contact_name',
        'status',
        'escalation_reason',
        'escalated_at',
        'last_customer_message_at',
        'last_ai_reply_at',
        'message_count',
        'metadata',
    ];

    protected $casts = [
        'escalated_at' => 'datetime',
        'last_customer_message_at' => 'datetime',
        'last_ai_reply_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function whatsappNumber()
    {
        return $this->belongsTo(WhatsappNumber::class);
    }

    public function messages()
    {
        return $this->hasMany(ConversationMessage::class)->orderBy('created_at', 'asc');
    }

    public function latestMessages(int $limit = 20)
    {
        return $this->messages()
            ->reorder()
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    public function isWithin24HourWindow(): bool
    {
        if (!$this->last_customer_message_at) {
            return false;
        }

        return $this->last_customer_message_at->gt(now()->subHours(24));
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeEscalated($query)
    {
        return $query->where('status', 'escalated');
    }
}
