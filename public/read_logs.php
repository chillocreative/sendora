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

echo "\n\nLAST 200 LINES OF STDERR LOG:\n";
$errLog = __DIR__.'/../whatsapp-server-temp/stderr.log';
if (file_exists($errLog)) {
    $lines = explode("\n", file_get_contents($errLog));
    echo implode("\n", array_slice($lines, -200));
} else {
    echo "stderr.log NOT FOUND at $errLog\n";
}
