<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$settings = \App\Models\Setting::all();
foreach($settings as $s) {
    echo $s->key . " => " . $s->value . "\n";
}
