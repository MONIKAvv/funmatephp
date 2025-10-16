<?php

  header("Content-Type: application/json");
  header("Access-Control-Allow-Origin: *");
  header("Access-Control-Allow-Methods: POST");
  header("Access-Control-Allow-Headers: Content-Type");

  include "db_connection.php";

  try {
    
    $stmt = $pdo->query("SELECT * FROM URLDetails");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
      "success" => true,
      "message" => $result
    ]);

  } catch (PDOException $e) {

    echo json_encode([
 "success" => false,
    "message" => "Query failed: ".$e->getMessage()
    ]);
  }

  error_reporting(E_ALL);
  ini_set('display_error', 1);

?>