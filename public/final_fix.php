<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;

header('Content-Type: text/html');
echo "<h1>FINAL RECOVERY SCRIPT</h1>";

// Ensure settings are correct
Setting::updateOrCreate(['key' => 'wa_server_url'], ['value' => 'https://sendora.cc/whatsapp-server-temp']);
Setting::updateOrCreate(['key' => 'app_url'], ['value' => 'https://sendora.cc']);
Artisan::call('optimize:clear');

echo "<h2>NODE LOG</h2>";
$log = __DIR__.'/../whatsapp-server-temp/server.log';
if (file_exists($log)) {
    echo "<pre>" . htmlspecialchars(file_get_contents($log)) . "</pre>";
} else {
    echo "Log file not found.<br>";
}

echo "<h2>WHATSAPP STATUS</h2>";
try {
    $resp = file_get_contents("https://sendora.cc/whatsapp-server-temp/status");
    echo "Status Response: " . htmlspecialchars($resp) . "<br>";
} catch(\Exception $e) {
    echo "Status Error: " . $e->getMessage() . "<br>";
}
