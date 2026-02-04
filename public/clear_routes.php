<?php
// public/clear_routes.php

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "<pre style='font-family: monospace; padding: 20px; background: #f0f0f0;'>";
echo "<strong>🚀 System Cache Diagnostics & Reset</strong>\n\n";

try {
    use Illuminate\Support\Facades\Artisan;
    
    echo "1. Clearing Route Cache...\n";
    Artisan::call('route:clear');
    echo "   " . trim(Artisan::output()) . "\n\n";
    
    echo "2. Clearing Config Cache...\n";
    Artisan::call('config:clear');
    echo "   " . trim(Artisan::output()) . "\n\n";
    
    echo "3. Clearing Compiled Classes...\n";
    Artisan::call('clear-compiled');
    echo "   " . trim(Artisan::output()) . "\n\n";
    
    echo "4. Re-listing Routes (grep 'cancel')...\n";
    Artisan::call('route:list');
    $routes = Artisan::output();
    $matches = array_filter(explode("\n", $routes), function($line) {
        return str_contains($line, 'cancel');
    });
    
    echo "   Found matching routes:\n";
    foreach ($matches as $match) {
        echo "   " . trim($match) . "\n";
    }
    
    echo "\n✅ <strong>SUCCESS: System caches cleared.</strong>";
    echo "\nNOTE: Please verify that '/subscription/cancel-plan' appears in the list above.";
    
} catch (\Throwable $e) {
    echo "\n❌ ERROR: " . $e->getMessage();
    echo "\n" . $e->getTraceAsString();
}

echo "</pre>";
