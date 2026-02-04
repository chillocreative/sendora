<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'user_id',
        'whatsapp_number_id',
        'name',
        'message_type',
        'body',
        'media_path',
        'scheduled_at',
        'status',
        'is_drip',
        'drip_delay_minutes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'is_drip' => 'boolean',
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
        return $this->hasMany(CampaignMessage::class);
    }

    /**
     * Get campaign statistics
     */
    public function getStatsAttribute()
    {
        $total = $this->messages()->count();
        $sent = $this->messages()->whereIn('status', ['sent', 'delivered', 'read'])->count();
        $delivered = $this->messages()->whereNotNull('delivered_at')->count();
        $read = $this->messages()->whereNotNull('read_at')->count();
        $clicked = $this->messages()->whereNotNull('clicked_at')->count();
        $failed = $this->messages()->where('status', 'failed')->count();
        $pending = $this->messages()->where('status', 'pending')->count();

        return [
            'total' => $total,
            'sent' => $sent,
            'delivered' => $delivered,
            'read' => $read,
            'clicked' => $clicked,
            'failed' => $failed,
            'pending' => $pending,
            'send_rate' => $total > 0 ? round(($sent / $total) * 100, 1) : 0,
            'delivery_rate' => $sent > 0 ? round(($delivered / $sent) * 100, 1) : 0,
            'open_rate' => $delivered > 0 ? round(($read / $delivered) * 100, 1) : 0,
            'click_rate' => $read > 0 ? round(($clicked / $read) * 100, 1) : 0,
        ];
    }
}
