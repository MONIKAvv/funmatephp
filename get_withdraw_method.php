<?php

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

error_reporting(E_ALL);
ini_set('display_error', 1);

include "db_connection.php";

try {
  //code...
  $stmt = $pdo->query("SELECT * FROM withdraw_method");
  $result - $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    "success" => true,
    "data" => $result
  ]);

} catch (PDOException $e) {

  echo json_encode([
    "success" => false,
    "message" => "Query Failed:".$e->getMessage()
  ]);
  //throw $th;
}

?>