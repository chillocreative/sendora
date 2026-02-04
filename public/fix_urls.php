<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;

echo "<h1>ENVIRONMENT DEBUG</h1>";
echo "APP_ENV: " . env('APP_ENV') . "<br>";
echo "DB_CONNECTION: " . config('database.default') . "<br>";
if (config('database.default') == 'mysql') {
    echo "DB_DATABASE: " . config('database.connections.mysql.database') . "<br>";
}

// Fix Settings
Setting::updateOrCreate(['key' => 'app_url'], ['value' => 'https://sendora.cc']);
Setting::updateOrCreate(['key' => 'wa_server_url'], ['value' => 'http://127.0.0.1:3000']);

echo "<h2>RESETTING CACHES</h2>";
echo "Config: " . (Artisan::call('config:clear') == 0 ? "CLEARED" : "FAILED") . "<br>";
echo "Route: " . (Artisan::call('route:clear') == 0 ? "CLEARED" : "FAILED") . "<br>";
echo "Optimize: " . (Artisan::call('optimize:clear') == 0 ? "CLEARED" : "FAILED") . "<br>";

echo "<h2>URL FIX COMPLETED</h2>";
echo "<p>Please delete this file (public/fix_urls.php) after running it.</p>";
