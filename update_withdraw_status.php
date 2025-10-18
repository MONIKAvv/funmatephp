<?php
header("Content-Type: application/json; charset=utf-8");
include "db_connection.php";

// This should be called by admin panel when status is changed
$data = json_decode(file_get_contents("php://input"), true);

$withdraw_id = $data["withdraw_id"] ?? 0;
$new_status = $data["status"] ?? ""; // 'successful' or 'rejected'

if (!$withdraw_id || !in_array($new_status, ['successful', 'rejected'])) {
    echo json_encode(["success" => false, "message" => "Invalid parameters"]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Get withdrawal details
    $stmt = $pdo->prepare("SELECT user_id, withdrawal_coin, left_coin, status FROM withdraw WHERE id = ?");
    $stmt->execute([$withdraw_id]);
    $withdraw = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$withdraw) {
        throw new Exception("Withdrawal record not found");
    }

    if ($withdraw['status'] !== 'pending') {
        throw new Exception("This withdrawal has already been processed");
    }

    $user_id = $withdraw['user_id'];
    $withdrawal_coin = $withdraw['withdrawal_coin'];
    $left_coin = $withdraw['left_coin'];

    if ($new_status === 'successful') {
        // Update withdrawal status
        $stmt = $pdo->prepare("UPDATE withdraw SET status = 'successful', processed_at = NOW() WHERE id = ?");
        $stmt->execute([$withdraw_id]);

        // Deduct coins from user
        $stmt = $pdo->prepare("UPDATE users SET coins = ? WHERE id = ?");
        $stmt->execute([$left_coin, $user_id]);

        $message = "Withdrawal approved and coins deducted";
    } else { // rejected
        // Just update status, don't touch user's coins
        $stmt = $pdo->prepare("UPDATE withdraw SET status = 'rejected', processed_at = NOW() WHERE id = ?");
        $stmt->execute([$withdraw_id]);

        $message = "Withdrawal rejected. User can submit a new request";
    }

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => $message
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>