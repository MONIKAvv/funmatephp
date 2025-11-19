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

    // 1. Get data from the Android app
    $data = json_decode(file_get_contents("php://input"), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON received.");
    }

    $withdrawalMethodId = $data["withdrawal_method_id"] ?? null;
    $coins = $data["coins"] ?? null;
    $email = $data["email"] ?? null;
    // ✅ FIX: Use "method_name" (snake_case) to match the JSON key from Android
    $methodName = $data["method_name"] ?? "";

    if ($withdrawalMethodId === null || $coins === null || $email === null) {
        throw new Exception("Missing required fields.");
    }

    // --- START TRANSACTION ---
    $pdo->beginTransaction();

    // 2. INSERT into `gift_codes` table.
    // NOTE: Make sure your `gift_codes` table has a column named "methodName" (camelCase)
    $stmtInsertGiftCode = $pdo->prepare(
        "INSERT INTO gift_codes (withdrawal_method_id, methodName, coins, assigned_to, assigned_at) VALUES (?, ?, ?, ?, NOW())"
    );
    $stmtInsertGiftCode->execute([$withdrawalMethodId, $methodName, $coins, $email]);

    // 3. GET USER ID
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmtUser->execute([$email]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        throw new Exception("User not found.");
    }
    $userId = $user["id"];

    // 4. CREATE record in `withdraw` table.
    // NOTE: Make sure your `withdraw` table has a column named "method_name" (snake_case)
    $stmtInsertHistory = $pdo->prepare(
        "INSERT INTO withdraw (user_id, email, withdrawal_coin, status, withdraw_method_id, method_name) VALUES (?, ?, ?, 'pending', ?, ?)"
    );
    $stmtInsertHistory->execute([$userId, $email, $coins, $withdrawalMethodId, $methodName]);
    
    // 5. DEDUCT coins from the user's balance
    $stmtDeductCoins = $pdo->prepare("UPDATE users SET coins = coins - ? WHERE id = ?");
    $stmtDeductCoins->execute([$coins, $userId]);

    // --- COMMIT ALL CHANGES ---
    $pdo->commit();

    // 6. Send a success response
    echo json_encode([
        "success" => true,
        "message" => "Redemption request submitted successfully!"
    ]);

} catch (Exception $e) {
    // If any step fails, undo everything
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
