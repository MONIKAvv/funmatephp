<?php
// db_connection.php
$host = 'localhost';
$dbname = 'monika_FunMate'; 
$username = 'monika_monika'; 
$password = 'monik@funm@te'; 
  try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // enable exception mode
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // optional: fetch results as assoc by default
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(json_encode(["success" => false, "message" => "DB Connection failed: " . $e->getMessage()]));
}
?>