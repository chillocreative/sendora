<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$entries = App\Models\AutoReply::all();
foreach ($entries as $entry) {
    echo "ID: {$entry->id}, User: {$entry->user_id}, Keyword: '{$entry->keyword}', Active: {$entry->is_active}\n";
}
