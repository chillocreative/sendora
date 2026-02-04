<?php
header('Content-Type: text/plain');
echo "ROOT DIR: " . realpath(__DIR__.'/..') . "\n";
echo "FILES IN ROOT:\n";
print_r(scandir(__DIR__.'/..'));
echo "\nFILES IN PUBLIC:\n";
print_r(scandir(__DIR__));
