<?php
/**
 * Simple API Test Script
 *
 * Usage:
 * php test-api.php YOUR_API_TOKEN
 */

if ($argc < 2) {
    echo "Usage: php test-api.php YOUR_API_TOKEN [BASE_URL]\n";
    echo "Example: php test-api.php 1|abc123... https://sendora.cc\n";
    exit(1);
}

$token = $argv[1];
$baseUrl = $argv[2] ?? 'https://sendora.cc';
$apiUrl = $baseUrl . '/api/v1';

echo "🧪 Sendora API Test Suite\n";
echo "=========================\n\n";
echo "Base URL: $baseUrl\n";
echo "Token: " . substr($token, 0, 10) . "...\n\n";

function makeRequest($url, $token, $method = 'GET', $data = null) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local testing
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json',
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    if ($error) {
        return ['error' => $error, 'http_code' => $httpCode];
    }

    return [
        'http_code' => $httpCode,
        'body' => json_decode($response, true),
    ];
}

function printResult($testName, $response) {
    echo "📋 Test: $testName\n";
    echo "   Status: " . $response['http_code'];

    if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
        echo " ✅\n";
    } else {
        echo " ❌\n";
    }

    if (isset($response['error'])) {
        echo "   Error: " . $response['error'] . "\n";
    } elseif (isset($response['body'])) {
        echo "   Response: " . json_encode($response['body'], JSON_PRETTY_PRINT) . "\n";
    }

    echo "\n";
}

// Test 1: Get Profile
$result = makeRequest("$apiUrl/profile", $token);
printResult('Get User Profile', $result);

if ($result['http_code'] !== 200) {
    echo "❌ Authentication failed. Please check your token.\n";
    exit(1);
}

// Test 2: Get Usage Stats
$result = makeRequest("$apiUrl/usage", $token);
printResult('Get Usage Statistics', $result);

// Test 3: Get Devices
$result = makeRequest("$apiUrl/devices", $token);
printResult('Get WhatsApp Devices', $result);

$hasConnectedDevice = false;
if (isset($result['body']['data'])) {
    foreach ($result['body']['data'] as $device) {
        if ($device['status'] === 'connected') {
            $hasConnectedDevice = true;
            echo "   ✅ Found connected device: " . ($device['phone_number'] ?? 'N/A') . "\n\n";
            break;
        }
    }
}

if (!$hasConnectedDevice) {
    echo "   ⚠️  No connected devices found. Skipping message sending test.\n\n";
}

// Test 4: Get Contacts (first 5)
$result = makeRequest("$apiUrl/contacts?per_page=5", $token);
printResult('Get Contacts (5 results)', $result);

// Test 5: Create a test contact
$testContact = [
    'name' => 'API Test Contact',
    'phone' => '60199999999',
    'email' => 'apitest@example.com',
];

$result = makeRequest("$apiUrl/contacts", $token, 'POST', $testContact);
printResult('Create Test Contact', $result);

if ($result['http_code'] === 201) {
    echo "   ℹ️  Note: You may want to delete this test contact from your dashboard\n\n";
}

// Test 6: Send Message (only if device is connected)
if ($hasConnectedDevice) {
    echo "⚠️  Skipping message sending test to avoid sending real messages.\n";
    echo "   To test message sending, uncomment the code in test-api.php\n\n";

    /*
    $testMessage = [
        'phone' => '60123456789', // CHANGE THIS
        'message' => 'Test message from Sendora API',
    ];

    $result = makeRequest("$apiUrl/messages/send", $token, 'POST', $testMessage);
    printResult('Send WhatsApp Message', $result);
    */
}

// Test 7: Get Campaigns
$result = makeRequest("$apiUrl/campaigns?per_page=5", $token);
printResult('Get Campaigns (5 results)', $result);

// Summary
echo "\n=========================\n";
echo "✅ API Testing Complete!\n";
echo "=========================\n\n";

echo "Next steps:\n";
echo "1. Review the API documentation in API_TESTING_GUIDE.md\n";
echo "2. Test message sending with a real phone number\n";
echo "3. Integrate the API into your application\n\n";
