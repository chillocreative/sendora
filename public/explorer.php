<?php
header('Content-Type: text/plain');
echo "EXPLORER V3\n";
try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo "\nSETTINGS:\n";
    $settings = \Illuminate\Support\Facades\DB::table('settings')->get();
    foreach($settings as $s) {
        echo "{$s->key} = {$s->value}\n";
    }

    echo "\nNODE HEALTH:\n";
    $health = @file_get_contents('https://sendora.cc/whatsapp-server-temp/health');
    echo $health ?: "FAILED TO REACH NODE";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
