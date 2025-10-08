<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

error_reporting(E_ALL);
ini_set('display_errors', 1);

include "db_connection.php";

$raw = file_get_contents("php://input");

if (empty($raw)) {
    echo json_encode(["success" => false, "message" => "No input data received"]);
    exit;
}

$data = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["success" => false, "message" => "Invalid JSON: " . json_last_error_msg()]);
    exit;
}

// Extract data
$uid = $data['uid'] ?? '';
$name = $data['name'] ?? '';
$email = $data['email'] ?? '';
$device_id = $data['device_id'] ?? '';
$idToken = $data['idToken'] ?? '';
$coins = intval($data['coins'] ?? 0);
$currentIndex = intval($data['currentIndex'] ?? 0);
$subject = strtolower($data['subject'] ?? '');
$today = date('Y-m-d');

if (empty($uid) || empty($email)) {
    echo json_encode(["success" => false, "message" => "Missing required fields: uid or email"]);
    exit;
}

// Map subjects to database columns
$subjectColumns = [
    'alphabetfun' => 'alphabet_current_index',
    'bigvssmall' => 'bigvssmall_current_index',
 
    'mathfun' => 'math_current_index',
    'soundfun' => 'sound_current_index',
       'dailycheckin' => 'daily_checkin_current_index',
    'gamefun' => 'game_fun_current_index',
];

$subjectDateColumns = [
    'alphabetfun' => 'alphabet_fun_last_date',
    'bigvssmall' => 'bigvssmall_fun_last_date',
    'dailycheckin' => 'daily_checkin_last_date',
    'gamefun' => 'game_fun_last_date',
    'mathfun' => 'math_fun_last_date',
    'soundfun' => 'sound_fun_last_date',
];

$dailyLimitColumn = $subjectColumns[$subject] ?? null;
$dateColumn = $subjectDateColumns[$subject] ?? null;

error_log("📥 Processing $subject: coins=$coins, index=$currentIndex, date=$today");

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE uid = ? OR email = ?");
    $stmt->execute([$uid, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // User exists - UPDATE
        $userId = $user['id'];
        $currentCoins = intval($user['coins'] ?? 0);

        if ($dailyLimitColumn && $dateColumn) {
            $currentSubjectLimit = intval($user[$dailyLimitColumn] ?? 0);
            $lastDate = $user[$dateColumn] ?? '';

            // 🔥 DAILY RESET - Reset if new day
            if ($lastDate !== $today) {
                error_log("🔄 NEW DAY! Resetting $subject from $currentSubjectLimit to 0");
                $currentSubjectLimit = 0;
            }

            $newSubjectLimit = $currentIndex;
        } else {
            $newSubjectLimit = 0;
        }

        // Always accumulate coins
        $newTotalCoins = $currentCoins + $coins;

        // Build update query
        $updateFields = "uid = ?, name = ?, email = ?, device_id = ?, coins = ?";
        $params = [$uid, $name, $email, $device_id, $newTotalCoins];

        if ($dailyLimitColumn && $dateColumn) {
            $updateFields .= ", $dailyLimitColumn = ?, $dateColumn = ?";
            $params[] = $newSubjectLimit;
            $params[] = $today;
        }

        $params[] = $userId;

        $updateStmt = $pdo->prepare("UPDATE users SET $updateFields WHERE id = ?");
        $updateStmt->execute($params);

        error_log("✅ Updated user $userId: coins=$newTotalCoins, {$subject}_index=$newSubjectLimit");

    } else {
        // New user - INSERT
        $insertFields = ["uid","name","email","device_id","idToken","coins"];
        $insertValues = [$uid, $name, $email, $device_id, $idToken, $coins];

        if ($dailyLimitColumn && $dateColumn) {
            $insertFields[] = $dailyLimitColumn;
            $insertFields[] = $dateColumn;
            $insertValues[] = $currentIndex;
            $insertValues[] = $today;
        }

        $placeholders = implode(',', array_fill(0, count($insertValues), '?'));
        $insertStmt = $pdo->prepare("INSERT INTO users (" . implode(',', $insertFields) . ") VALUES ($placeholders)");
        $insertStmt->execute($insertValues);
        $userId = $pdo->lastInsertId();
        $newTotalCoins = $coins;
        $newSubjectLimit = $currentIndex;

        error_log("✅ Created user $userId: coins=$coins, {$subject}_index=$currentIndex");
    }

    // Generate token
    $token = bin2hex(random_bytes(16));
    $tokenStmt = $pdo->prepare("UPDATE users SET token = ? WHERE id = ?");
    $tokenStmt->execute([$token, $userId]);

    // Fetch full user data
    $allDataStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $allDataStmt->execute([$userId]);
    $fullUserData = $allDataStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "token" => $token,
        "message" => "Data saved successfully for $subject",
        "user" => [
            "id" => $userId,
            "email" => $email,
            "coins" => $newTotalCoins,
            "alphabet_current_index" => intval($fullUserData['alphabet_current_index'] ?? 0),
            "math_current_index" => intval($fullUserData['math_current_index'] ?? 0),
            "bigvssmall_current_index" => intval($fullUserData['bigvssmall_current_index'] ?? 0),
            "updated_subject" => $subject,
            "updated_index" => $newSubjectLimit ?? 0
        ]
    ]);

} catch (PDOException $e) {
    error_log("❌ Error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>