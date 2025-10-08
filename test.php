<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo json_encode([
    "debug" => "PHP file is working",
    "method" => $_SERVER['REQUEST_METHOD'],
    "time" => date('Y-m-d H:i:s')
]);

// Test 1: Check if we can read input
$raw = file_get_contents("php://input");
error_log("TEST - RAW INPUT: " . $raw);

// Test 2: Try database connection
try {
    error_log("TEST - Attempting database connection...");
    include "db_connection.php";
    error_log("TEST - Database connection successful");
    echo "\n" . json_encode(["database" => "connected"]);
} catch (Exception $e) {
    error_log("TEST - Database connection failed: " . $e->getMessage());
    echo "\n" . json_encode(["database" => "failed", "error" => $e->getMessage()]);
}

// Test 3: Try Google API call (with dummy token)
try {
    error_log("TEST - Testing Google API connection...");
    $testUrl = "https://oauth2.googleapis.com/tokeninfo?id_token=dummy";
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'method' => 'GET',
            'ignore_errors' => true
        ]
    ]);
    $result = file_get_contents($testUrl, false, $context);
    error_log("TEST - Google API test completed (expected to fail with dummy token)");
    echo "\n" . json_encode(["google_api" => "reachable"]);
} catch (Exception $e) {
    error_log("TEST - Google API test failed: " . $e->getMessage());
    echo "\n" . json_encode(["google_api" => "failed", "error" => $e->getMessage()]);
}
?>