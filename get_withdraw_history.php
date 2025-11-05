<?php
header("Content-Type: application/json; charset=utf-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "db_connection.php";

$data = json_decode(file_get_contents("php://input"), true);
$email = $data["email"] ?? "";

if (!$email) {
    echo json_encode([
        "success" => false,
        "message" => "Email is required"
    ]);
    exit;
}

// Get user info
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
$user_coins = $user["coins"];

// ✅ Sync coins to withdraw table
$check = $pdo->prepare("SELECT id FROM withdraw WHERE user_id = ?");
$check->execute([$user_id]);

if ($check->rowCount() > 0) {
    // Update existing record
    $stmt = $pdo->prepare("UPDATE withdraw SET total_coins = ? WHERE user_id = ?");
    $stmt->execute([$user_coins, $user_id]);
} else {
    // Insert new record if not exists
    $stmt = $pdo->prepare("INSERT INTO withdraw (user_id, total_coins) VALUES (?, ?)");
    $stmt->execute([$user_id, $user_coins]);
}

// ✅ Fetch withdraw history joined with method details
$query = "
SELECT 
    w.id, 
    w.total_coins, 
    w.withdrawal_coin, 
    w.left_coin, 
    w.status, 
    w.requested_at, 
    w.processed_at, 
    m.method_name, 
    w.sub_category, 
    m.upi_id, 
    m.bank_name
FROM withdraw AS w
LEFT JOIN withdraw_methods AS m ON w.withdraw_method_id = m.id
WHERE w.user_id = ?
ORDER BY w.requested_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Return JSON response
echo json_encode([
    "success" => true,
    "data" => $history
    // history containing all the details like totalcoin as well 
]);
?>
