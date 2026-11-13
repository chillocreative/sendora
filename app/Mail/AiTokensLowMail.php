<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AiTokensLowMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public int $remaining,
        public int $allowance,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->remaining > 0
            ? 'Your Sendora AI tokens are running low'
            : 'You have used all your Sendora AI tokens');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.ai-tokens-low');
    }
}
