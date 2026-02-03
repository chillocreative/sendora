<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;

$starter = SubscriptionPlan::where('name', 'Starter')->first();
echo "Starter ID: " . $starter->id . "\n";

$rastaman = User::where('name', 'RASTAMAN')->first();
if ($rastaman) {
    $sub = $rastaman->latestSubscription;
    if (!$sub) {
        echo "RASTAMAN has no subscription. Creating Starter subscription...\n";
        $rastaman->subscriptions()->create([
            'subscription_plan_id' => $starter->id,
            'status' => 'active',
            'ends_at' => now()->addYears(10), // Long term for free plan
        ]);
        echo "Subscription created.\n";
    } else {
        echo "RASTAMAN already has a subscription: " . $sub->plan->name . "\n";
    }
} else {
    echo "User RASTAMAN not found.\n";
}
