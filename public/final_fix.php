<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;

header('Content-Type: text/html');
echo "<h1>FINAL RECOVERY SCRIPT</h1>";

// 1. SET PUBLIC URL (More reliable for cPanel/Passenger)
$publicUrl = "https://sendora.cc/whatsapp-server-temp";
Setting::updateOrCreate(['key' => 'wa_server_url'], ['value' => $publicUrl]);
echo "WA_SERVER_URL set to: $publicUrl<br>";

Setting::updateOrCreate(['key' => 'app_url'], ['value' => "https://sendora.cc"]);
echo "APP_URL set to: https://sendora.cc<br>";

// 2. FORCE CLEAR ALL CACHES
Artisan::call('optimize:clear');
echo "Caches Purged.<br>";

echo "<h2>SYSTEM RESTORED</h2>";
echo "Please wait 10 seconds, then go back to WhatsApp Manager and refresh.";
