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
    ];

    protected $casts = [
        'phone_info' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }}
