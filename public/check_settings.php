<?php
// public/check_settings.php
echo "<h1>Settings Check</h1><pre>";
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

use App\Models\Setting;

$waUrl = Setting::where('key', 'wa_server_url')->value('value');
echo "wa_server_url in DB: " . ($waUrl ?: "NOT SET") . "\n";

$appUrl = Setting::where('key', 'app_url')->value('value');
echo "app_url in DB: " . ($appUrl ?: "NOT SET") . "\n";

echo "\nENV WA_SERVER_URL: " . env('WA_SERVER_URL') . "\n";
echo "ENV APP_URL: " . env('APP_URL') . "\n";

echo "</pre>";
