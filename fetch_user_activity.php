<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include "db_connection.php";

$email = $_GET['email']?? '';

if($email == ''){
  echo json_encode([
    "success" => false,
    "message" => "Email required"
  ]);
}

try {
  
  $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->execute([$email]);
  $user_details = $stmt->fetch(PDO::FETCH_ASSOC);

  if($user_details){
    echo json_encode([
      "success" => true,
      "data" => $user_details
    ]);
  }else{
    echo json_encode([
      "success" => false,
      "message" => "No data found in users table"
    ]);
  }

} catch (PDOException $e) {
  echo json_encode([
    "success" => false,
    "message" => "Query failed: ". $e->getMessage()
  ]);
}
 
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>