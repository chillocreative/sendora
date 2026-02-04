<?php
header('Content-Type: text/plain');
echo "ROOT DIR: " . realpath(__DIR__.'/..') . "\n";
echo "FILES IN ROOT:\n";
print_r(scandir(__DIR__.'/..'));
echo "\nREADING whatsapp-server-temp/server.log:\n";
$log = __DIR__.'/../whatsapp-server-temp/server.log';
if (file_exists($log)) {
    echo file_get_contents($log);
} else {
    echo "Log file NOT FOUND at $log\n";
}
