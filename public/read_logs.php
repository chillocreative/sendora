<?php
// public/read_logs.php
echo "<h1>Laravel Logs (Last 50 lines)</h1><pre>";
$logFile = __DIR__ . '/../storage/logs/laravel.log';
if (file_exists($logFile)) {
    echo shell_exec("tail -n 50 " . escapeshellarg($logFile));
} else {
    echo "Log file not found.";
}
echo "</pre>";
