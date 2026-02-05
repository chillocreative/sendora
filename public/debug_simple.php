<?php
// public/debug_simple.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple Connectivity Test</h1>";
echo "<pre>";

$target = "127.0.0.1";
$port = 3000;

echo "Checking if port $port is open on $target...\n";

$connection = @fsockopen($target, $port, $errno, $errstr, 2);

if (is_resource($connection)) {
    echo "✅ SUCCESS: Port $port is OPEN.\n";
    fclose($connection);
    
    echo "\nAttempting to get /health from Node server...\n";
    $url = "http://127.0.0.1:3000/health";
    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
    $result = @file_get_contents($url, false, $ctx);
    
    if ($result) {
        echo "✅ Node Server Response: $result\n";
    } else {
        echo "❌ Port is open but server did not respond to /health. (Maybe wrong path or app failed)\n";
    }
} else {
    echo "❌ FAILED: Port $port is CLOSED.\n";
    echo "Error: $errstr ($errno)\n";
}

echo "\n--- System Info ---\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Working Dir: " . getcwd() . "\n";

echo "</pre>";
