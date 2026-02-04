<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

echo "<h1>SYSTEM PURGE</h1>";

// 1. Clear Laravel Caches
Artisan::call('optimize:clear');
echo "Laravel Caches: CLEARED<br>";

// 2. Fix URLs and Settings
\App\Models\Setting::updateOrCreate(['key' => 'app_url'], ['value' => 'https://sendora.cc']);
\App\Models\Setting::updateOrCreate(['key' => 'wa_server_url'], ['value' => 'http://127.0.0.1:3000']);
echo "Settings: UPDATED (using local bridge)<br>";

// 3. Create Storage Link
try {
    Artisan::call('storage:link');
    echo "Storage Link: RECREATED<br>";
} catch (\Exception $e) {
    echo "Storage Link: " . $e->getMessage() . "<br>";
}

// 4. Wipe Node Sessions
$sessionPath = __DIR__.'/../whatsapp-server-temp/sessions';
if (File::exists($sessionPath)) {
    File::deleteDirectory($sessionPath);
    File::makeDirectory($sessionPath, 0755, true);
    echo "Node Sessions: WIPED<br>";
} else {
    echo "Node Session path not found: $sessionPath<br>";
}

// 5. Restart Node (via Passenger trigger)
$restartFile = __DIR__.'/../whatsapp-server-temp/tmp/restart.txt';
File::ensureDirectoryExists(__DIR__.'/../whatsapp-server-temp/tmp');
File::put($restartFile, time());
echo "Restart Trigger: SENT<br>";

echo "<h2>SYSTEM READY FOR FRESH SYNC</h2>";
echo "Please go to WhatsApp Manager and Reconnect your device.";
