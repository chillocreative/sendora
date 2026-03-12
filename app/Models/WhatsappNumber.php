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
    ];

    protected $casts = [
        'phone_info' => 'array',
        'ai_reply_enabled' => 'boolean',
    ];

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
