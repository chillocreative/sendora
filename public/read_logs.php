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
$nodeDir = __DIR__ . '/../whatsapp-server-temp';
$nodeLog = $nodeDir . '/server.log';

// Diagnostic: Check directory and node_modules
echo "Checking directory: $nodeDir\n";
if (is_dir($nodeDir)) {
    echo "Files in directory:\n" . shell_exec("ls -F " . escapeshellarg($nodeDir));
    if (is_dir("$nodeDir/node_modules")) {
        echo "\n✅ node_modules found.\n";
    } else {
        echo "\n❌ node_modules MISSING! (Server cannot start)\n";
    }
} else {
    echo "❌ Directory $nodeDir not found!\n";
}

echo "\n--- Last 50 lines of server.log ---\n";
if (file_exists($nodeLog)) {
    echo shell_exec("tail -n 50 " . escapeshellarg($nodeLog));
} else {
    echo "WhatsApp Node log file not found. Ensure the server is running.\n";
    echo "NPM Path: " . shell_exec("which npm 2>&1") . "\n";
    echo "Node Path: " . shell_exec("which node 2>&1") . "\n";
}
echo "</pre>";
