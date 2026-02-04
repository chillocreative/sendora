<?php
header('Content-Type: text/plain');

echo "\nLAST 500 LINES OF LARAVEL LOG:\n";
$log = __DIR__.'/../storage/logs/laravel.log';
if (file_exists($log)) {
    $content = file_get_contents($log);
    $lines = explode("\n", $content);
    echo implode("\n", array_slice($lines, -500)) . "\n";
} else {
    echo "Laravel log NOT FOUND\n";
}

echo "\nLAST 500 LINES OF NODE LOG:\n";
$nodeLog = __DIR__.'/../whatsapp-server-temp/server.log';
if (file_exists($nodeLog)) {
    $content = file_get_contents($nodeLog);
    $lines = explode("\n", $content);
    echo implode("\n", array_slice($lines, -500)) . "\n";
} else {
    echo "Node log NOT FOUND\n";
}

echo "\nSETTINGS CHECK:\n";
try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    $settings = \Illuminate\Support\Facades\DB::table('settings')->get();
    foreach($settings as $s) {
        echo "{$s->key} = {$s->value}\n";
    }

    echo "\nNODE HEALTH CHECK:\n";
    $healthUrl = 'https://sendora.cc/whatsapp-server-temp/health';
    $health = @file_get_contents($healthUrl);
    echo "URL: $healthUrl\n";
    echo "RESPONSE: $health\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
