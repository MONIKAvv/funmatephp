<?php
header("Content-Type: application/json");
include "db_connection.php";

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;           // promo id
$email = $data['email'] ?? null;     // user email
$coins = intval($data['coins'] ?? 0); // coins from promo

if (!$id || !$email) {
    echo json_encode(["success" => false, "message" => "Promo ID and User Email required"]);
    exit;
}

try {
    // 1. Increase promo usage
    $stmt = $pdo->prepare("UPDATE promocode SET total_used = total_used + 1 WHERE id = ?");
    $stmt->execute([$id]);

    // 2. Increase user coins
    $updateCoin = $pdo->prepare("UPDATE users SET coins = coins + ? WHERE email = ?");
    $updateCoin->execute([$coins, $email]);

    if ($stmt->rowCount() > 0 && $updateCoin->rowCount() > 0) {
        echo json_encode(["success" => true, "message" => "Promo applied, coins updated"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update promo or coins"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
