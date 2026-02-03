<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$app->boot();

$campaign = \App\Models\Campaign::where('name', 'bBolaaaa')->first();
if ($campaign) {
    echo "Status: " . $campaign->status . "\n";
    echo "Sent: " . $campaign->success_count . "\n";
    echo "Failed: " . $campaign->failure_count . "\n";
} else {
    echo "Campaign not found.\n";
}
