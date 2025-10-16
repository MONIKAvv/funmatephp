<?php

  header("Content-Type: application/json; charset=utf-8");

  include "db_connection.php";

  $data = json_decode(file_get_contents("php://input"), true);
  $email = $data["email"] ?? "";

  if(!$email){
    echo json_encode([
      "success" => false,
      "message" => "Email is required"
    ]);
  }
  // getting user_id from email
  $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
  $stmt->execute([$email]);
  $user = $stmt->Fetch(PDO::FETCH_ASSOC);

  if(!$user){
    echo json_encode([
      "success" => false, 
      "message" => "User not found"
    ]);
  }
  $user_id = $user["id"];
  // Now fetching withdraw history joined with method details

  $query = "
  SELECT w.id, w.total_coins, w.withdrawal_coin, w.left_coin, w.status, w.requested_at, w.processed_at, m.upi_id, m.bank_name FROM withdraw AS w
  LEFT JOIN withdraw_methods AS m ON w.withdraw_method_id = m.id WHERE w.user_id = ? 
  ORDER BY w.requested_at DESC
  ";

  $stmt = $pdo->prepare($query);
  $stmt->execute([$user_id]);
  $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
  "success" => true, 
  "data" => $history]);


?>