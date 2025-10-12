<?php
// db_connection.php
$host = 'localhost';
$dbname = 'monika_FunMate'; 
$username = 'monika_monika'; 
$password = 'monik@funm@te'; 
 try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    // ❌ Don't echo here! Just log
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    die(json_encode([
        "success" => false,
        "message" => "Database connection failed"
    ]));
}
?>