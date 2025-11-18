<?php
// Set headers for JSON response
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle pre-flight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

// It's good practice to include your connection inside a try-catch block
try {
    include "db_connection.php"; // Make sure $pdo is created here

    // 1. Get the data sent from the Android app
    $data = json_decode(file_get_contents("php://input"), true);

    // Check if json_decode worked. If not, the input was bad.
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON received from client.");
    }

    // 2. Get the variables safely from the decoded data
    $withdrawalMethodId = $data["withdrawal_method_id"] ?? null;
    $coins = $data["coins"] ?? null;
    $pin = $data["pin"] ?? ""; // Pin can be an empty string
    $assignedToEmail = $data["email"] ?? null;

    // 3. Validate that required data is present
    if ($withdrawalMethodId === null || $coins === null || $assignedToEmail === null) {
        echo json_encode([
            "success" => false,
            "message" => "Missing required fields (withdrawal_method_id, coins, or email)."
        ]);
        http_response_code(400); // Bad Request
        exit;
    }

    // 4. Prepare the SQL INSERT statement
    // The database will automatically generate the 'id', 'code', 'assigned_at', and 'created_at' values.
    $sql = "INSERT INTO gift_codes (withdrawal_method_id, coins, pin, assigned_to) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    // 5. Execute the query with the data
    $success = $stmt->execute([$withdrawalMethodId, $coins, $pin, $assignedToEmail]);

    // 6. Check if the insert was successful and send a clear response
    if ($success) {
        echo json_encode([
            "success" => true,
            "message" => "Redemption successful! Code has been assigned."
        ]);
    } else {
        throw new Exception("Failed to save the gift code to the database.");
    }

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        "success" => false,
        "message" => "Database Error: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        "success" => false,
        "message" => "Server Error: " . $e->getMessage()
    ]);
}

?>
