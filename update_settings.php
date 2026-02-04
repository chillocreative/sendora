<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\App\Models\Setting::updateOrCreate(
    ['key' => 'wa_server_url'], 
    ['value' => 'https://sendora.cc/whatsapp-server-temp']
);

\App\Models\Setting::updateOrCreate(
    ['key' => 'app_url'], 
    ['value' => 'https://sendora.cc']
);

echo "Settings Updated!\n";
unlink(__FILE__);
