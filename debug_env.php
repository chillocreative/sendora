<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$numbers = DB::table('whatsapp_numbers')->get();
foreach($numbers as $n) {
    print_r($n);
}
echo "APP_URL: " . (\App\Models\Setting::where('key', 'app_url')->value('value') ?? config('app.url')) . "\n";
echo "WA_SERVER_URL: " . (\App\Models\Setting::where('key', 'wa_server_url')->value('value') ?? env('WA_SERVER_URL')) . "\n";
