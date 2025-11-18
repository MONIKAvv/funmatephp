<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

header("Access-Control-Allow-MethodsL POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");



if (ob_get_level()) {
    ob_end_clean();
}

// Check if the ID was provided in the URL
if (!isset($_GET['withdrawal_method_id'])) {
    // If no ID is provided, send a clear error message.
    http_response_code(400); 
    echo json_encode([
        "success" => false,
        "message" => "Required parameter 'withdrawal_method_id' is missing."
    ]);
    exit(); 
}

try {
    require "db_connection.php";

    if (!isset($pdo)) {
        throw new Exception("Database connection object (pdo) not found.");
    }

    $pdo->exec("set names utf8");

  

    // 1. Get the ID from the URL.
    $withdrawal_method_id = $_GET['withdrawal_method_id'];

    // 2. Prepare the SQL query with a placeholder (?)
    $sql = "SELECT * FROM sub_categories WHERE withdrawal_method_id = ?";
    $stmt = $pdo->prepare($sql);

    // 3. Execute the statement, safely passing the ID
    $stmt->execute([$withdrawal_method_id]);

    // 4. Fetch the results
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $result
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database Query failed: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "An error occurred: " . $e->getMessage()
    ]);
}
?>
