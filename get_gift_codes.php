<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if(ob_get_level){
  ob_end_clean();
}
try {
 
  require "db_connection.php";
  if(!isset($pdo)){
    throw new Exception("Database connection pdo not found.");
  }

  $pdo->exec("set names utf-8");

  $stmt = $pdo->query("SELECT * FROM git_codes");
  $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $email = $result["assigned_to"];
  $code = $result["code"];
  $withdrawal_coin = $result["withdrawal_coin"];
  $withdrawal_method_id = $result["withdrawal_method_id"];

  $stmt = $pdo->query("SELECT id FROM withdraw");
  $id = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // insert to withdraw table
  $stmt = $pdo->prepare("UPDATE withdraw SET email = ?, code =?, withdrawal_coin =?, withdrawal_method_id = ? WHERE id = ?");
  $data->execute([$email, $code, $withdrawal_coin, $withdrawal_method_id, $id ]);

  echo json_encode([
    "success" => true,
    "data" => $result
  ]);

}catch(PDOException $e){
  http_response_code(500);
  echo json_encode([
    "success" => false,
    "message" => "Database query failed ". $e->getMessage()
  ]);

} 

catch (Exception $e) {

 http_response_code(500);
 echo json_encode([
  "success" => false,
  "message" => "An error occured: ".$e->getMessage()
 ]);
}

?>