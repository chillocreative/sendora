<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutoReply extends Model
{
    protected $fillable = [
        'user_id',
        'keyword',
        'reply_message',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
