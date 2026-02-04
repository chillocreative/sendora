<?php
$logFile = __DIR__.'/../whatsapp-server-temp/server.log';
if (file_exists($logFile)) {
    header('Content-Type: text/plain');
    echo file_get_contents($logFile);
} else {
    echo "Node log not found at: " . $logFile . "\n";
    // Try to list files in the folder
    echo "FILES IN whatsapp-server-temp:\n";
    print_r(scandir(__DIR__.'/../whatsapp-server-temp'));
}
