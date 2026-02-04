<?php
header('Content-Type: text/plain');
$log = __DIR__.'/../storage/logs/laravel.log';
if (file_exists($log)) {
    echo "LAST 200 LINES OF LARAVEL LOG:\n";
    $lines = explode("\n", file_get_contents($log));
    echo implode("\n", array_slice($lines, -200));
} else {
    echo "Log file NOT FOUND at $log\n";
}

echo "\n\nLAST 200 LINES OF NODE LOG:\n";
$nodeLog = __DIR__.'/../whatsapp-server-temp/server.log';
if (file_exists($nodeLog)) {
    $lines = explode("\n", file_get_contents($nodeLog));
    echo implode("\n", array_slice($lines, -200));
} else {
    echo "Node log NOT FOUND at $nodeLog\n";
}
