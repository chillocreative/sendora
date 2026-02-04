<?php
echo "SEARCHING FOR server.log...\n";
$dirs = [
    __DIR__.'/../whatsapp-server-temp',
    __DIR__.'/../',
    __DIR__
];

foreach ($dirs as $dir) {
    if (file_exists($dir.'/server.log')) {
        echo "FOUND AT: $dir/server.log\n";
        echo "--- CONTENT ---\n";
        echo file_get_contents($dir.'/server.log');
        exit;
    }
}

echo "LOG NOT FOUND. LISTING FILES:\n";
foreach ($dirs as $dir) {
    echo "IN $dir:\n";
    if (file_exists($dir)) {
        print_r(scandir($dir));
    } else {
        echo "DIR NOT FOUND\n";
    }
}
