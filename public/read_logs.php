<?php
// public/read_logs.php
echo "<h1>System Logs Console</h1>";

echo "<h2>1. Laravel Application Logs</h2><pre style='background:#f4f4f4; pading:10px;'>";
$logFile = __DIR__ . '/../storage/logs/laravel.log';
if (file_exists($logFile)) {
    echo shell_exec("tail -n 50 " . escapeshellarg($logFile));
} else {
    echo "Laravel log file not found.";
}
echo "</pre>";

echo "<h2>2. WhatsApp Node Server Logs</h2><pre style='background:#e8f4f8; padding:10px;'>";
$nodeLog = __DIR__ . '/../whatsapp-server-temp/server.log';
if (file_exists($nodeLog)) {
    echo shell_exec("tail -n 50 " . escapeshellarg($nodeLog));
} else {
    echo "WhatsApp Node log file not found. Ensure the server is running and logs are enabled.";
}
echo "</pre>";
