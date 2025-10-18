<?php
header("Content-Type: application/json; charset=utf-8");
include "db_connection.php";

try {
    $data = json_decode(file_get_contents("php://input"), true);

    // Log the incoming request for debugging
    error_log("Withdrawal Request: " . json_encode($data));

    $email = $data["email"] ?? "";
    $method_name = $data["method"] ?? "";
    $account_holder_name = $data["accountHolder"] ?? "";
    $upi_id = $data["upiId"] ?? "";
    $bank_name = $data["bankName"] ?? "";
    $account_number = $data["accountNo"] ?? "";
    $ifsc_code = $data["ifscCode"] ?? "";
    $phone_number = $data["phoneNo"] ?? "";

    if (!$email) {
        echo json_encode([
            "success" => false, 
            "message" => "Email is required"
        ]);
        exit;
    }

    if (!$method_name) {
        echo json_encode([
            "success" => false, 
            "message" => "Payment method is required"
        ]);
        exit;
    }

    // Step 1: Get user info
    $stmt = $pdo->prepare("SELECT id, coins FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            "success" => false, 
            "message" => "User not found"
        ]);
        exit;
    }

    $user_id = $user["id"];
    $total_coins = (int)$user["coins"];

    // Step 2: Check if user already has a pending request
    $pendingCheck = $pdo->prepare("SELECT id FROM withdraw WHERE user_id = ? AND status = 'pending'");
    $pendingCheck->execute([$user_id]);
    $pending = $pendingCheck->fetch(PDO::FETCH_ASSOC);

    if ($pending) {
        echo json_encode([
            "success" => false, 
            "message" => "You already have a pending withdrawal request. Please wait until it's processed."
        ]);
        exit;
    }

    // Step 3: Check eligibility
    $withdraw_coin = 10; // minimum coins required
    if ($total_coins < $withdraw_coin) {
        echo json_encode([
            "success" => false, 
            "message" => "Not enough coins to withdraw. You need at least 10 coins."
        ]);
        exit;
    }

    // Use transaction for data consistency
    $pdo->beginTransaction();

    // Step 4: Save withdraw method
    $stmt = $pdo->prepare("
        INSERT INTO withdraw_methods 
        (user_id, method_name, account_holder_name, upi_id, bank_name, account_number, ifsc_code, phone_number, email)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id, 
        $method_name, 
        $account_holder_name, 
        $upi_id, 
        $bank_name, 
        $account_number, 
        $ifsc_code, 
        $phone_number, 
        $email
    ]);
    $withdraw_method_id = $pdo->lastInsertId();

    // Step 5: Calculate left coins (but don't apply yet)
    $left_coin = $total_coins - $withdraw_coin;

    // Step 6: Insert withdraw record with status = 'pending'
    $stmt = $pdo->prepare("
        INSERT INTO withdraw (user_id, total_coins, withdrawal_coin, left_coin, status, withdraw_method_id)
        VALUES (?, ?, ?, ?, 'pending', ?)
    ");
    $stmt->execute([$user_id, $total_coins, $withdraw_coin, $left_coin, $withdraw_method_id]);

    // Commit transaction
    $pdo->commit();

    // DO NOT update user's coins - only when admin approves
    echo json_encode([
        "success" => true,
        "message" => "Withdrawal request submitted successfully. Waiting for approval.",
        "current_coins" => $total_coins
    ]);

} catch (PDOException $e) {
    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Database Error: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Database error occurred. Please try again."
    ]);
    
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "An error occurred: " . $e->getMessage()
    ]);
}
?>