<?php

namespace App\Mail;

use App\Models\Reminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReminderEmailNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Reminder $reminder,
        public string $type = 'created',
    ) {}

    public function envelope(): Envelope
    {
        $dateTime = $this->reminder->event_at ?? $this->reminder->reminder_at;
        $dateStr = $dateTime->format('D, d M Y \a\t g:i A');

        $subject = $this->type === 'due'
            ? "Upcoming: {$this->reminder->title}"
            : "Reminder: {$this->reminder->title} on {$dateStr}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.reminder-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
