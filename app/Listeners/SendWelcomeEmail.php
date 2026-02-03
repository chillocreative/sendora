<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendWelcomeEmail
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        try {
            \Illuminate\Support\Facades\Mail::to($event->user->email)->send(new \App\Mail\WelcomeEmail());
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send welcome email to {$event->user->email}: " . $e->getMessage());
        }
    }
}
