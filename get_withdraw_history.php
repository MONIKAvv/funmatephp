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
    $query = "
    SELECT
        w.id,
        w.user_id,
        w.email,
        w.code,
        w.withdrawal_coin,
        w.status,
        w.requested_at,
        w.withdraw_method_id,
        m.method_name
    FROM withdraw AS w
    LEFT JOIN withdraw_methods AS m ON w.withdraw_method_id = m.id
    WHERE w.user_id = ?
    ORDER BY w.requested_at DESC
    ";

    $stmtHistory = $pdo->prepare($query);
    $stmtHistory->execute([$user_id]);
    $history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

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
