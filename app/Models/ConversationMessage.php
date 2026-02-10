<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'direction',
        'sender_type',
        'body',
        'wa_message_id',
        'confidence_score',
        'reasoning_source',
        'escalation_reason',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'latency_ms',
        'ai_metadata',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:3',
        'ai_metadata' => 'array',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
