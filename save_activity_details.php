<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

error_reporting(0);
ini_set('display_errors', 0);

include "db_connection.php";

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

$uid = $input['uid'] ?? null;
$email = $input['email'] ?? null;
$coins = intval($input['coins'] ?? 0);
$subject = $input['subject'] ?? null;
$currentIndex = intval($input['currentIndex'] ?? 1); // Make sure this is sent!
$today = date('Y-m-d');

if (!$email || !$uid || !$subject || $currentIndex === null || $coins === null) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}


try {
    // 🎯 Map subject to correct database columns
    $indexColumn = '';
    $dateColumn = '';
    
    switch (strtolower($subject)) {
        case 'alphabetfun':
            $indexColumn = 'alphabet_current_index';
            $dateColumn = 'alphabet_fun_last_date';
            break;
        case 'bigvssmall':
            $indexColumn = 'bigvssmall_current_index';
            $dateColumn = 'bigvssmall_fun_last_date';
            break;
        case 'mathfun':
        case 'math':
            $indexColumn = 'math_current_index';
            $dateColumn = 'math_fun_last_date';
            break;
        case 'soundfun':
        case 'sound':
            $indexColumn = 'sound_current_index';
            $dateColumn = 'sound_fun_last_date';
            break;
        case 'gamefun':
            $indexColumn = 'game_current_index';
            $dateColumn = 'game_fun_last_date';
            break;
        case 'dailycheckin':
            $indexColumn = 'daily_checkin_current_index';
            $dateColumn = 'daily_checkin_last_date';
            break;
        default:
            echo json_encode(["success" => false, "message" => "Invalid subject"]);
            exit;
    }

    // ✅ Update coins, current index, AND last date
    $sql = "UPDATE users 
            SET coins = coins + ?,
                $indexColumn = ?,
                $dateColumn = ?
            WHERE email = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$coins, $currentIndex, $today, $email]);

    // ✅ Fetch updated data to return
    $fetchStmt = $pdo->prepare("
        SELECT 
            coins,
            alphabet_current_index,
            bigvssmall_current_index,
            math_current_index,
            sound_current_index,
            daily_checkin_current_index,
            game_current_index
        FROM users 
        WHERE email = ?
    ");
    $fetchStmt->execute([$email]);
    $user = $fetchStmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode([
            "success" => true,
            "message" => "Data saved successfully for $subject",
            "user" => [
                "email" => $email,
                "coins" => intval($user['coins']),
                "alphabet_current_index" => intval($user['alphabet_current_index']),
                "bigvssmall_current_index" => intval($user['bigvssmall_current_index']),
                "math_current_index" => intval($user['math_current_index']),
                "sound_current_index" => intval($user['sound_current_index']),
                "daily_checkin_current_index" => intval($user['daily_checkin_current_index']),
                "game_current_index" => intval($user['game_current_index']),
                "updated_subject" => $subject,
                "updated_index" => $currentIndex
            ]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "User not found"]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "success" => false, 
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
