<?php
header('Content-Type: text/plain');
echo "ROOT DIR: " . realpath(__DIR__.'/..') . "\n";
echo "FILES IN ROOT:\n";
print_r(scandir(__DIR__.'/..'));
echo "\nFILES IN whatsapp-server-temp:\n";
$waDir = __DIR__.'/../whatsapp-server-temp';
if (file_exists($waDir)) {
    print_r(scandir($waDir));
} else {
    echo "whatsapp-server-temp folder NOT FOUND\n";
}
