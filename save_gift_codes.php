<?php
// Set headers for JSON response
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle pre-flight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

try {
    include "db_connection.php";

    // 1. Get the data from the Android app (SubCategoryActivity)
    $data = json_decode(file_get_contents("php://input"), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON received.");
    }

    $withdrawalMethodId = $data["withdrawal_method_id"] ?? null;
    $coins = $data["coins"] ?? null;
    $email = $data["email"] ?? null;

    if ($withdrawalMethodId === null || $coins === null || $email === null) {
        throw new Exception("Missing required fields.");
    }

    // --- This script only performs two main actions ---
    $pdo->beginTransaction();

    // 2. Get the user's ID from the 'users' table
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmtUser->execute([$email]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        throw new Exception("Redemption failed: User not found.");
    }
    $userId = $user["id"];

    // 3. ✅ CREATE THE PENDING RECORD in the `withdraw` table
    // The 'code' is intentionally left out, so it will be NULL.
    $stmtInsertHistory = $pdo->prepare(
        "INSERT INTO withdraw (user_id, email, withdrawal_coin, status, withdraw_method_id) VALUES (?, ?, ?, 'pending', ?)"
    );
    $stmtInsertHistory->execute([$userId, $email, $coins, $withdrawalMethodId]);

    // 4. DEDUCT coins from the user's balance
    $stmtDeductCoins = $pdo->prepare("UPDATE users SET coins = coins - ? WHERE id = ?");
    $stmtDeductCoins->execute([$coins, $userId]);

    $pdo->commit();

    // 5. Send a success response back to the app
    echo json_encode([
        "success" => true,
        "message" => "Redemption request submitted successfully! Your request is being processed."
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Request Error: " . $e->getMessage()
    ]);
}
?>
