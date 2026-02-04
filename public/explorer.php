<?php
header('Content-Type: text/plain');
echo "ROOT DIR: " . realpath(__DIR__.'/..') . "\n";
echo "FILES IN ROOT:\n";
print_r(scandir(__DIR__.'/..'));
echo "\nREADING whatsapp-server-temp/server.js:\n";
$js = __DIR__.'/../whatsapp-server-temp/server.js';
if (file_exists($js)) {
    echo file_get_contents($js);
} else {
    echo "server.js NOT FOUND at $js\n";
}
