<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;

// Fix Settings
Setting::updateOrCreate(['key' => 'app_url'], ['value' => 'https://sendora.cc']);
Setting::updateOrCreate(['key' => 'wa_server_url'], ['value' => 'http://127.0.0.1:3000']);

// Clear Caches
Artisan::call('optimize:clear');
Artisan::call('config:clear');
Artisan::call('route:clear');
Artisan::call('view:clear');

echo "<h1>URL FIX COMPLETED</h1>";
echo "<ul>";
echo "<li>APP_URL set to https://sendora.cc</li>";
echo "<li>WA_SERVER_URL set to https://sendora.cc/whatsapp-server-temp</li>";
echo "<li>All caches cleared</li>";
echo "</ul>";
echo "<p>Please delete this file (public/fix_urls.php) after running it.</p>";
