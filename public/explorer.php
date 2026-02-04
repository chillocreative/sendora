<?php
header('Content-Type: text/plain');
echo "ROOT DIR: " . realpath(__DIR__.'/..') . "\n";
echo "FILES IN ROOT:\n";
print_r(scandir(__DIR__.'/..'));
echo "\nSETTINGS TABLE:\n";
try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    $settings = \Illuminate\Support\Facades\DB::table('settings')->get();
    print_r($settings);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
