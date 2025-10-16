<?php
header("Content-Type: application/json; charset=utf-8");
include "db_connection.php";

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["success" => false, "message" => "No data received"]);
    exit;
}

$email = $data["email"] ?? "";
$method_name = $data["method"] ?? "";
$account_holder_name = $data["accountHolder"] ?? "";
$holder_name = $data["holderName"] ?? "";
$upi_id = $data["upiId"] ?? "";
$bank_name = $data["bankName"] ?? "";
$account_number = $data["accountNo"] ?? "";
$ifsc_code = $data["ifscCode"] ?? "";
$phone_number = $data["phoneNo"] ?? "";

if (!$email) {
    echo json_encode(["success" => false, "message" => "Email is required"]);
    exit;
}

// Step 1: Get user_id and total_coins from users table
$stmt = $pdo->prepare("SELECT id, coins FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit;
}

$user_id = $user["id"];
$total_coins = $user["coins"];

// Step 2: Insert into withdraw_methods
$stmt = $pdo->prepare("
    INSERT INTO withdraw_methods 
    (user_id, method_name, account_holder_name, upi_id, bank_name, account_number, ifsc_code, phone_number, email)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$success = $stmt->execute([
    $user_id, $method_name, $account_holder_name, $upi_id, $bank_name, 
    $account_number, $ifsc_code, $phone_number, $email
]);

if (!$success) {
    echo json_encode(["success" => false, "message" => "Failed to save withdraw method"]);
    exit;
}

// Get the last inserted withdraw_method_id
$withdraw_method_id = $pdo->lastInsertId();

// Step 3: Insert into withdraw table
$withdraw_coin = 100; // for now, you can set a fixed or dynamic value
$left_coin = $total_coins - $withdraw_coin;

$stmt = $pdo->prepare("
    INSERT INTO withdraw (user_id, total_coins, withdrawal_coin, left_coin, status, withdraw_method_id)
    VALUES (?, ?, ?, ?, 'pending', ?)
");

$success2 = $stmt->execute([
    $user_id, $total_coins, $withdraw_coin, $left_coin, $withdraw_method_id
]);

if ($success2) {
    echo json_encode([
        "success" => true,
        "message" => "Withdrawal details and request saved successfully"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to save withdrawal request"
    ]);
}
?>
