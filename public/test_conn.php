<?php
header('Content-Type: text/plain');
echo "TESTING LOCALHOST:3000\n";
$start = microtime(true);
$ch = curl_init('http://127.0.0.1:3000/health');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $code\n";
echo "Error: $err\n";
echo "Body: $response\n";
echo "Time: " . (microtime(true) - $start) . "s\n";

echo "\nTESTING PUBLIC URL\n";
$start = microtime(true);
$ch = curl_init('https://sendora.cc/whatsapp-server-temp/health');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $code\n";
echo "Error: $err\n";
echo "Body: $response\n";
echo "Time: " . (microtime(true) - $start) . "s\n";
