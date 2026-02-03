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
        
        $campaigns = Campaign::where('status', 'scheduled')
            ->where('scheduled_at', '<=', $now)
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info('No scheduled campaigns due for dispatch.');
            return;
        }

        foreach ($campaigns as $campaign) {
            $this->info("Dispatching campaign: {$campaign->name} (Scheduled for: {$campaign->scheduled_at})");
            
            // Update status to pending so it gets picked up by the job
            $campaign->update(['status' => 'pending']);
            
            ProcessCampaignJob::dispatch($campaign);
        }

        $this->info($campaigns->count() . ' campaigns dispatched successfully.');
    }
}
