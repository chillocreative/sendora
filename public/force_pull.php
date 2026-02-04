<?php
header('Content-Type: text/plain');
echo "FORCE PULL START\n";
$output = [];
$return_var = 0;
exec("cd .. && git fetch origin main 2>&1", $output);
exec("cd .. && git reset --hard origin/main 2>&1", $output);
echo implode("\n", $output);
echo "\nFORCE PULL END\n";
