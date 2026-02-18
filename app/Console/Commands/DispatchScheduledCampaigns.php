<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Campaign;
use App\Jobs\ProcessCampaignJob;
use Carbon\Carbon;

class DispatchScheduledCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:dispatch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find scheduled campaigns that are ready to be sent and dispatch them.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        // Dispatch scheduled campaigns that are due
        $scheduledCampaigns = Campaign::where('status', 'scheduled')
            ->where('scheduled_at', '<=', $now)
            ->get();

        foreach ($scheduledCampaigns as $campaign) {
            $this->info("Dispatching scheduled campaign: {$campaign->name} (Scheduled for: {$campaign->scheduled_at})");
            $campaign->update(['status' => 'pending']);
            ProcessCampaignJob::dispatch($campaign);
        }

        if ($scheduledCampaigns->isNotEmpty()) {
            $this->info($scheduledCampaigns->count() . ' scheduled campaigns dispatched.');
        }

        // Safety net: re-dispatch immediate campaigns stuck in 'pending' for more than 2 minutes.
        // This handles cases where the queue worker was not running when the campaign was created.
        $stalePending = Campaign::where('status', 'pending')
            ->whereNull('scheduled_at')
            ->where('created_at', '<=', Carbon::now()->subMinutes(2))
            ->get();

        foreach ($stalePending as $campaign) {
            $this->info("Re-dispatching stale pending campaign: {$campaign->name} (created: {$campaign->created_at})");
            ProcessCampaignJob::dispatch($campaign);
        }

        if ($stalePending->isNotEmpty()) {
            $this->info($stalePending->count() . ' stale pending campaigns re-dispatched.');
        }

        if ($scheduledCampaigns->isEmpty() && $stalePending->isEmpty()) {
            $this->info('No campaigns due for dispatch.');
        }
    }
}
