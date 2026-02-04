<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WhatsappNumber;
use App\Models\User;

header('Content-Type: text/plain');
echo "USERS:\n";
foreach(User::all() as $u) {
    echo "ID: {$u->id}, Email: {$u->email}\n";
}

echo "\nWHATSAPP NUMBERS:\n";
foreach(WhatsappNumber::all() as $n) {
    echo "ID: {$n->id}, User ID: {$n->user_id}, Status: {$n->status}, Phone: {$n->phone_number}\n";
}
