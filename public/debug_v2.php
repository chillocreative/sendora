<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

header('Content-Type: text/html');
echo "<h1>ENVIRONMENT DEBUG V2</h1>";
echo "APP_URL: " . config('app.url') . "<br>";
echo "DB_CONNECTION: " . config('database.default') . "<br>";

// Fix Settings
Setting::updateOrCreate(['key' => 'app_url'], ['value' => 'https://sendora.cc']);
Setting::updateOrCreate(['key' => 'wa_server_url'], ['value' => 'http://127.0.0.1:3000']);

echo "<h2>RESETTING CACHES</h2>";
Artisan::call('config:clear');
Artisan::call('optimize:clear');

echo "<h2>DUMPING NUMBERS</h2>";
try {
    $numbers = DB::table('whatsapp_numbers')->select('id', 'user_id', 'status')->get();
    foreach($numbers as $n) {
        echo "ID: {$n->id}, User: {$n->user_id}, Status: {$n->status}<br>";
    }
} catch(\Exception $e) {
    echo "Error: " . $e->getMessage();
}
echo "<h2>COMPLETED</h2>";
