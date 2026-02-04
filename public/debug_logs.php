<?php
header('Content-Type: text/plain');

echo "\nLAST 500 LINES OF LARAVEL LOG:\n";
$log = __DIR__.'/../storage/logs/laravel.log';
if (file_exists($log)) {
    $content = file_get_contents($log);
    $lines = explode("\n", $content);
    $lastLines = array_slice($lines, -500);
    echo implode("\n", $lastLines) . "\n";
} else {
    echo "Laravel log NOT FOUND at $log\n";
    // Try to find it manually
    echo "Current dir: " . __DIR__ . "\n";
}

echo "\nLAST 500 LINES OF NODE LOG:\n";
$nodeLog = __DIR__.'/../whatsapp-server-temp/server.log';
if (file_exists($nodeLog)) {
    $content = file_get_contents($nodeLog);
    $lines = explode("\n", $content);
    echo implode("\n", array_slice($lines, -500)) . "\n";
} else {
    echo "Node log NOT FOUND at $nodeLog\n";
}
