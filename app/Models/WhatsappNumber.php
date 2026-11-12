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
        'provider',
        'waba_id',
        'phone_number_id',
        'display_phone_number',
        'access_token',
        'token_expires_at',
        'quality_rating',
        'messaging_limit',
        'webhook_subscribed_at',
    ];

    protected $hidden = [
        'access_token',
    ];

    protected $casts = [
        'phone_info' => 'array',
        'ai_reply_enabled' => 'boolean',
        'access_token' => 'encrypted',
        'token_expires_at' => 'datetime',
        'webhook_subscribed_at' => 'datetime',
    ];

    public function isCloud(): bool
    {
        return $this->provider === 'cloud';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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
