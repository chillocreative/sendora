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
        'is_warmer_pool_enabled',
        'warmer_daily_limit',
        'warmer_messages_sent_today',
        'warmer_last_chatted_at',
    ];

    protected $casts = [
        'phone_info' => 'array',
        'warmer_last_chatted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }}
