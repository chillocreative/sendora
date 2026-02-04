<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProcessAIWarmer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'warmer:process-pool';
    protected $description = 'Process AI-AI conversations in the warmer pool';

    public function handle()
    {
        $this->info('🚀 Starting AI Warmer Pool Processing...');

        // 1. Get all eligible numbers
        $pool = \App\Models\WhatsappNumber::where('is_warmer_pool_enabled', true)
            ->where('status', 'connected')
            ->get();

        if ($pool->count() < 2) {
            $this->warn('Pool needs at least 2 connected numbers to operate.');
            return 0;
        }

        // 2. Iterate and pair
        foreach ($pool as $number) {
            // Auto-reset daily count if it's a new day
            if ($number->warmer_last_chatted_at && !$number->warmer_last_chatted_at->isToday()) {
                $number->update(['warmer_messages_sent_today' => 0]);
                $number->refresh();
            }

            // Check daily limit
            if ($number->warmer_messages_sent_today >= $number->warmer_daily_limit) {
                $this->info("Skipping {$number->phone_number}: Daily limit reached.");
                continue;
            }

            // Find a target number from the pool (not self)
            $target = $pool->where('id', '!=', $number->id)
                ->where('is_warmer_pool_enabled', true)
                ->shuffle()
                ->first();

            if (!$target) continue;

            // 3. Check if there's an existing conversation to reply to
            $lastConversation = \DB::table('warmer_logs')
                ->where('to_number_id', $number->id)
                ->where('from_number_id', $target->id)
                ->latest()
                ->first();

            $aiService = new \App\Services\OpenAiService();
            $message = $aiService->generateWarmerMessage($lastConversation?->message);

            $this->info("AI generating message from {$number->phone_number} to {$target->phone_number}...");

            // 4. Send the message via WhatsApp Service
            $waService = new \App\Services\WhatsappService();
            $response = $waService->sendMessage($number, $target->phone_number, $message);

            if ($response && $response->successful()) {
                // 5. Log the interaction
                \DB::table('warmer_logs')->insert([
                    'from_number_id' => $number->id,
                    'to_number_id' => $target->id,
                    'message' => $message,
                    'role' => $lastConversation ? 'replier' : 'starter',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // 6. Update stats
                $number->increment('warmer_messages_sent_today');
                $number->update(['warmer_last_chatted_at' => now()]);

                $this->info("✅ Message sent successfully.");
            } else {
                $this->error("❌ Failed to send message.");
            }

            // Random delay between pairs
            $delay = rand(10, 30);
            $this->info("Waiting {$delay}s...");
            sleep($delay);
        }

        $this->info('Done.');
        return 0;
    }
}
