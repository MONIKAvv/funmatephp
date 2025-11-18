    <?php
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // It's good practice to include GET
    header("Access-Control-Allow-Headers: Content-Type");

    // Clear any previous output
    if (ob_get_level()) {
        ob_end_clean();
    }

    try {
        // It's safer to include the file this way to prevent execution on failure
        require "db_connection.php";

        // This assumes $pdo is created in db_connection.php
        if (!isset($pdo)) {
            throw new Exception("Database connection object (pdo) not found.");
        }

        // It is recommended to set the character set for the connection
        $pdo->exec("set names utf8");

        $stmt = $pdo->query("SELECT * FROM withdraw_method");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "success" => true,
            "data" => $result
        ]);

    } catch (PDOException $e) {
        // Send a valid JSON response with the database error
        http_response_code(500); // Internal Server Error
        echo json_encode([
            "success" => false,
            "message" => "Database Query failed: " . $e->getMessage()
        ]);
    } catch (Exception $e) {
        // Catch other errors, like the connection file not being found
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "An error occurred: " . $e->getMessage()
        ]);
    }
    ?>
    