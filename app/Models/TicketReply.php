<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketReply extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'attachments',
        'is_admin',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'is_admin' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
