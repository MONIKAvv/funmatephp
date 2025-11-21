<?php

header('Content-Type: application/json');
require 'db_connection.php'; // This must provide $pdo (PDO connection)

// -------------------------------
// STEP 1: Get input & validate
// -------------------------------
// $email = $_POST['email'] ?? '';
// $amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 0;
// $method = $_POST['method'] ?? '';

// if (empty($email) || $amount <= 0 || empty($method)) {
//     http_response_code(400);
//     echo json_encode(['success' => false, 'message' => 'Invalid input provided.']);
//     exit();
// }

// -----------------------------------------------
// STEP 2: Get all activities (from activity_details)
// -----------------------------------------------
try {
    $activities_stmt = $pdo->query("SELECT subject, total_no_of_que FROM activity_details");
    $activities = $activities_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching activities.']);
    exit();
}

$all_activities = [];
foreach ($activities as $row) {
    $all_activities[$row['subject']] = (int)$row['total_no_of_que'];
}

// -------------------------------
// STEP 3: Fetch user progress
// -------------------------------
// try {
//     $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
//     $stmt->execute([$email]);
//     $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
// } catch (Exception $e) {
//     http_response_code(500);
//     echo json_encode(['success' => false, 'message' => 'Database error.']);
//     exit();
// }

$all_tasks_complete = true;

// If user not found:
if (!$user_data) {
    $all_tasks_complete = false;
} else {
    foreach ($all_activities as $subject => $required_questions) {

        $progress_column = str_replace('_fun', '', $subject) . "_current_index";
        $user_progress = isset($user_data[$progress_column]) ? (int)$user_data[$progress_column] : 0;

        if ($user_progress < $required_questions) {
            $all_tasks_complete = false;
            break;
        }
    }
}

// -------------------------------
// STEP 4: Reject withdrawal if tasks not complete
// -------------------------------
if (!$all_tasks_complete) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Complete all daily tasks before withdrawing.']);
    exit();
}

// -------------------------------
// STEP 5: Check user coins
// // -------------------------------
// $user_coins = (int)$user_data['coins'];

// if ($user_coins < $amount) {
//     http_response_code(400);
//     echo json_encode(['success' => false, 'message' => 'Not enough coins.']);
//     exit();
// }

// // -------------------------------
// // STEP 6: Deduct coins and process withdrawal
// // -------------------------------
// $new_coin_total = $user_coins - $amount;

// try {
//     $update_stmt = $pdo->prepare("UPDATE users SET coins = ? WHERE email = ?");
//     $update_stmt->execute([$new_coin_total, $email]);

//     // OPTIONAL: You can log withdrawal here.

//     echo json_encode([
//         'success' => true,
//         'message' => 'Withdrawal request submitted successfully!',
//         'new_coin_total' => $new_coin_total
//     ]);

// } catch (Exception $e) {
//     http_response_code(500);
//     echo json_encode(['success' => false, 'message' => 'Failed to process withdrawal.']);
// }

?>
