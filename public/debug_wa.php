<?php
// public/debug_wa.php

echo "<h1>WhatsApp Backend Debugger</h1>";
echo "<pre>";

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

$waUrl = Setting::where('key', 'wa_server_url')->value('value');
echo "1. Database Setting (wa_server_url): " . ($waUrl ?: "NOT SET") . "\n";

if (!$waUrl) {
    $waUrl = env('WA_SERVER_URL', 'http://localhost:3000');
    echo "   Fallback from ENV: $waUrl\n";
}

echo "\n2. Testing Connection to $waUrl/status/1/1 ...\n";

try {
    $start = microtime(true);
    $response = Http::withoutVerifying()->timeout(5)->get($waUrl . "/status/1/1");
    $end = microtime(true);
    
    echo "   Time taken: " . round($end - $start, 3) . "s\n";
    echo "   Response Code: " . $response->status() . "\n";
    echo "   Body: " . $response->body() . "\n";
    
    if ($response->successful()) {
        echo "\n✅ SUCCESS: PHP can reach the Node.js server.\n";
    } else {
        echo "\n❌ ERROR: Node.js server returned an error code.\n";
    }
} catch (\Exception $e) {
    echo "\n❌ TIMEOUT/CONNECTION ERROR: PHP cannot reach the Node.js server at $waUrl.\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   Possible reasons:\n";
    echo "   - Node server is not running.\n";
    echo "   - Port 3000 is blocked by a firewall.\n";
    echo "   - cPanel/Passenger is using a different port or socket.\n";
}

echo "\n3. Testing Local Node Logs (if accessible)...\n";
$logPath = __DIR__ . '/../whatsapp-server-temp/server.log';
if (file_exists($logPath)) {
    echo "   Log file found. Last 20 lines:\n";
    $lines = explode("\n", shell_exec("tail -n 20 " . escapeshellarg($logPath)));
    foreach ($lines as $line) {
        echo "   $line\n";
    }
} else {
    echo "   Log file not found at: $logPath\n";
}

echo "\n4. PHP Environment:\n";
echo "   PHP Version: " . PHP_VERSION . "\n";
echo "   User: " . posix_getpwuid(posix_geteuid())['name'] . "\n";

echo "</pre>";
