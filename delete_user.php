<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
include "db_connection.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Get user email from JSON input
$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? null;

if (!$email) {
    echo json_encode([
        "success" => false,
        "message" => "Email is required"
    ]);
    exit;
}

try {
    // ✅ Fetch user details
    $fetchStmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $fetchStmt->execute([$email]);
    $user = $fetchStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            "success" => false,
            "message" => "User not found"
        ]);
        exit;
    }

    // ✅ Insert user data into deleted_users table
    $insertStmt = $pdo->prepare("INSERT INTO deleted_users (email, name, coins, deleted_at) VALUES (?, ?, ?, NOW())");
    $insertStmt->execute([$user['email'], $user['name'], $user['coins']]);

    // ✅ Delete user from users table
    $deleteStmt = $pdo->prepare("DELETE FROM users WHERE email = ?");
    $deleteStmt->execute([$email]);

    // ✅ Return success
    echo json_encode([
        "success" => true,
        "message" => "$email deleted and archived successfully"
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
