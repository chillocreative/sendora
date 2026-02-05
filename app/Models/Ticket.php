<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'user_id',
        'subject',
        'description',
        'priority',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class);
    }

    public function latestReply()
    {
        return $this->hasOne(TicketReply::class)->latestOfMany();
    }

    public function unreadAdminReplies()
    {
        return $this->hasMany(TicketReply::class)
            ->where('is_admin', true)
            ->whereNull('read_at');
    }
}
