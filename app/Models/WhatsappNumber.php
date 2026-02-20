<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappNumber extends Model
{
    protected $fillable = [
        'user_id',
        'phone_number',
        'session_data',
        'status',
        'qr_code',
        'phone_info',
        'playbook_id',
        'ai_reply_enabled',
        'is_warmer_pool_enabled',
        'warmer_daily_limit',
        'warmer_messages_sent_today',
        'warmer_last_chatted_at',
    ];

    protected $casts = [
        'phone_info' => 'array',
        'ai_reply_enabled' => 'boolean',
        'is_warmer_pool_enabled' => 'boolean',
        'warmer_last_chatted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function playbook()
    {
        return $this->belongsTo(Playbook::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }
}
