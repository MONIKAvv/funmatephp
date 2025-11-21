<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "db_connection.php";

$email = $_GET['email'] ?? null;

if (!$email) {
    echo json_encode(["success" => false, "message" => "Email required"]);
    exit;
}
$today = date('Y-m-d');
try {
    $stmt = $pdo->prepare("SELECT 
        alphabet_current_index,
        bigvssmall_current_index,
        math_current_index,
        sound_current_index,
        daily_checkin_current_index,
        game_current_index,
        coins,
         alphabet_fun_last_date,
        bigvssmall_fun_last_date,
         daily_checkin_last_date,
        game_fun_last_date,
        math_fun_last_date,
        sound_fun_last_date
       
    FROM users WHERE email = ?");
   
    
    $stmt->execute([$email]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // 🔥 DAILY RESET LOGIC - Check if it's a new day for each subject
        // this is ternary operatior 
        $alphabetIndex = ($result['alphabet_fun_last_date'] == $today) 
            ? intval($result['alphabet_current_index']) : 0;
            
        $bigvssmallIndex = ($result['bigvssmall_fun_last_date'] == $today) 
            ? intval($result['bigvssmall_current_index']) : 0;
            
        $mathIndex = ($result['math_fun_last_date'] == $today) 
            ? intval($result['math_current_index']) : 0;
            
        $soundIndex = ($result['sound_fun_last_date'] == $today) 
            ? intval($result['sound_current_index']) : 0;
            
        $checkinIndex = ($result['daily_checkin_last_date'] == $today) 
            ? intval($result['daily_checkin_current_index']) : 0;
            
        $gamefunIndex = ($result['game_fun_last_date'] == $today) 
            ? intval($result['game_current_index']) : 0;

        echo json_encode([
            "success" => true,
            "data" => [
        "alphabet_current_index" => $alphabetIndex,
        "bigvssmall_current_index" => $bigvssmallIndex,
        "math_current_index" => $mathIndex,
        "sound_current_index" => $soundIndex,
        "daily_checkin_current_index" => $checkinIndex,
        "game_current_index" => $gamefunIndex,
        "coins" => intval($result['coins']),
        "alphabet_fun_last_date" => $result['alphabet_fun_last_date'],
         "bigvssmall_fun_last_date" => $result['bigvssmall_fun_last_date'],
          "math_fun_last_date" => $result['math_fun_last_date'],
           "sound_fun_last_date" => $result['sound_fun_last_date'],
            "daily_checkin_last_date" => $result['daily_checkin_last_date'],
             "game_fun_last_date" => $result['game_fun_last_date'],
        "today" => $today
    ]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "User not found"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>