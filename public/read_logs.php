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
        echo "✅ node_modules found in root.\n";
    } elseif (is_dir("$nodeDir/whatsapp-server-temp/node_modules")) {
        echo "⚠️ node_modules found in NESTED directory. (Path issue identified)\n";
    } else {
        echo "❌ node_modules MISSING in both places!\n";
    }
} else {
    echo "❌ Directory $nodeDir not found!\n";
}

echo "\n--- Environment Check ---\n";
echo "NPM Path (which): " . shell_exec("which npm 2>&1") . "\n";
echo "Node Path (which): " . shell_exec("which node 2>&1") . "\n";

echo "\nSearching even more system paths:\n";
$paths = [
    '/usr/local/bin/node',
    '/usr/bin/node',
    '/bin/node',
    '/opt/cpanel/ea-nodejs20/bin/node',
    '/opt/cpanel/ea-nodejs22/bin/node',
    '/opt/cpanel/ea-nodejs18/bin/node',
    '/opt/cpanel/ea-nodejs16/bin/node',
    '/opt/alt/alt-nodejs20/root/usr/bin/node',
    '/opt/alt/alt-nodejs18/root/usr/bin/node',
];
foreach($paths as $p) {
    if (file_exists($p)) echo "✅ Found NODE: $p\n";
}

$npmPaths = [
    '/usr/local/bin/npm',
    '/usr/bin/npm',
    '/opt/cpanel/ea-nodejs20/bin/npm',
    '/opt/cpanel/ea-nodejs22/bin/npm',
    '/opt/cpanel/ea-nodejs18/bin/npm',
    '/opt/alt/alt-nodejs20/root/usr/bin/npm',
];
foreach($npmPaths as $p) {
    if (file_exists($p)) echo "✅ Found NPM: $p\n";
}

echo "\nListing /usr/local/bin (looking for anything node-related):\n";
echo shell_exec("ls -F /usr/local/bin | grep node");
echo shell_exec("ls -F /usr/local/bin | grep npm");
echo "</pre>";
