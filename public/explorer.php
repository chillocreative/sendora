<?php
header('Content-Type: text/plain');
echo "<h1>DEPLOYMENT TEST SUCCESSFUL - VERSION 2.0</h1>";
echo "ROOT DIR: " . realpath(__DIR__.'/..') . "\n";
echo "FILES IN ROOT:\n";
print_r(scandir(__DIR__.'/..'));
echo "\nSYSTEM STATUS:\n";
try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo "Running storage:link...\n";
    \Illuminate\Support\Facades\Artisan::call('storage:link');
    echo "Artisan output: " . \Illuminate\Support\Facades\Artisan::output() . "\n";

    echo "\nSETTINGS:\n";
    $settings = \Illuminate\Support\Facades\DB::table('settings')->get();
    foreach($settings as $s) {
        echo "{$s->key} = {$s->value}\n";
    }

    echo "\nFILES IN PUBLIC/STORAGE:\n";
    if (file_exists(__DIR__.'/storage')) {
        print_r(scandir(__DIR__.'/storage'));
    } else {
        echo "public/storage folder NOT FOUND\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
