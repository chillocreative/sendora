<?php
$logFile = __DIR__.'/../storage/logs/laravel.log';
if (file_exists($logFile)) {
    header('Content-Type: text/plain');
    echo shell_exec("tail -n 100 " . escapeshellarg($logFile));
} else {
    echo "Log file not found: " . $logFile;
}
