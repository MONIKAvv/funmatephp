<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// to clear any previous output
if (ob_get_level()) {
    ob_end_clean();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    include "db_connection.php";

   // --- THIS IS THE ONLY CHANGE YOU NEED ---
   // Look for the email in the URL query parameters ($_GET)
 $data = json_decode(file_get_contents("php://input"), true);
$email = $data["email"] ?? "";


    if (!$email) {
        echo json_encode([
            "success" => false,
            "message" => "Email parameter is required in the URL."
        ]);
        exit;
    }

    // First, get the user's ID from the users table. This is good practice.
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmtUser->execute([$email]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            "success" => false,
            "message" => "User not found"
        ]);
        exit;
    }
    $user_id = $user["id"];

    // Now, fetch the history for that specific user ID.
$stmt = $pdo->prepare(
    "SELECT
        w.id,
        w.user_id,
        w.email,
        w.withdrawal_coin,
        w.status,
        w.withdraw_method_id,
        w.requested_at,
        w.method_name,  -- <-- Add this line
        gc.code         -- <-- This is from the JOIN in Problem 3
    FROM withdraw AS w
    LEFT JOIN gift_codes AS gc ON w.user_id = (SELECT id FROM users WHERE email = w.email) AND w.requested_at = gc.assigned_at
    WHERE w.email = ?
    ORDER BY w.id DESC"
);

$stmt->execute([$email]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the data found
    echo json_encode([
        "success" => true,
        "data" => $history
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "An error occurred: " . $e->getMessage()
    ]);
}
?>
