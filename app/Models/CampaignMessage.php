<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignMessage extends Model
{
    protected $fillable = [
        'campaign_id',
        'contact_id',
        'status',
        'wa_message_id',
        'sent_at',
        'delivered_at',
        'read_at',
        'clicked_at',
        'sequence_order',
        'delay_minutes',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
