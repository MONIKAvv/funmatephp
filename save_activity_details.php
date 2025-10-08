<?php
header("Content-Type: application/json");
include "db_connection.php";

$data = json_decode(file_get_contents("php://input"), true);

$email = $data['email'] ?? null;
$coins = intval($data['coins'] ?? 0);
$subject = $data['subject'] ?? null;
$currentIndex = intval($data['currentIndex'] ?? 0);

if (!$email) {
    echo json_encode(["success" => false, "message" => "Email required"]);
    exit;
}

try {
    // Determine which column to update based on subject
    $indexColumn = null;
    switch($subject) {
        case 'alphabetfun':
            $indexColumn = 'alphabet_current_index';
            break;
        case 'bigvssmall':
            $indexColumn = 'bigvssmall_current_index';
            break;
        case 'mathfun':
            $indexColumn = 'math_current_index';
            break;
        case 'soundfun':
            $indexColumn = 'sound_current_index';
            break;

        case 'dailycheckin':
                $indexColumn = 'daily_checkin_current_index';
                break;
        case 'gamefun':
            $indexColumn = 'game_fun_current_index';
            break;    
    }

    if ($indexColumn) {
        // Update coins AND current index
        $stmt = $pdo->prepare("UPDATE users SET coins = coins + ?, $indexColumn = ? WHERE email = ?");
        $stmt->execute([$coins, $currentIndex, $email]);
    } else {
        // Only update coins
        $stmt = $pdo->prepare("UPDATE users SET coins = coins + ? WHERE email = ?");
        $stmt->execute([$coins, $email]);
    }

    if ($stmt->rowCount() > 0) {
        // Fetch updated total coins
        $stmt = $pdo->prepare("SELECT coins FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "success" => true, 
            "message" => "User data updated successfully",
            "totalCoins" => $result['coins']
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "No user updated (maybe not found)"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>