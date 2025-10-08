<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include "db_connection.php"; // $pdo defined here

try {
    // Execute query
    $stmt = $pdo->query("SELECT * FROM promocode");

    // Fetch all rows as associative array
    $promocodes = $stmt->fetchAll(); // fetchAll(PDO::FETCH_ASSOC) is default if set in db_connection.php

    if (!empty($promocodes)) {
        echo json_encode([
            "success" => true,
            "data" => $promocodes
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "No data found"
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Query failed: " . $e->getMessage()
    ]);
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
