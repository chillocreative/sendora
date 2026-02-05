<?php
// public/fix_cache.php

echo "<h1>System Cache Fixer</h1>";
echo "<pre>";

$cachePath = __DIR__ . '/../bootstrap/cache';
$files = glob($cachePath . '/*.php');

if (empty($files)) {
    echo "No cache files found in bootstrap/cache.\n";
} else {
    foreach ($files as $file) {
        $name = basename($file);
        echo "Found cache file: $name ... ";
        if (unlink($file)) {
            echo "<span style='color:green'>DELETED</span>\n";
        } else {
            echo "<span style='color:red'>FAILED (Permission Denied)</span>\n";
        }
    }
}

// Attempt to recreate optimized files specifically without route caching first
try {
    echo "\nRunning artisan optimize:clear...\n";
    echo shell_exec('cd .. && php artisan optimize:clear');
    
    echo "\nRunning artisan view:clear...\n";
    echo shell_exec('cd .. && php artisan view:clear');
    
    echo "\nRunning artisan route:clear...\n";
    echo shell_exec('cd .. && php artisan route:clear');

} catch (Exception $e) {
    echo "Artisan command failed: " . $e->getMessage();
}

echo "\n\nDone. Please try the cancellation again.";
echo "</pre>";
