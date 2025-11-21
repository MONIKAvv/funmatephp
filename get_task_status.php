<?php
// File: get_task_status.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS, GET');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db_connection.php'; // This must create $pdo (PDO connection)

// Check if user email is provided
if (!isset($_GET['email']) || empty($_GET['email'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User email is required.']);
    exit();
}

$email = $_GET['email'];

// -------------------------------------------
// STEP 1: Fetch all activities
// -------------------------------------------
try {
    $activities_sql = $pdo->query("SELECT subject, total_no_of_que FROM activity_details");
    $activities = $activities_sql->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching activities: ' . $e->getMessage()]);
    exit();
}

// Convert activity list into associative array: [subject => total_questions]
$all_activities = [];
foreach ($activities as $row) {
    $all_activities[$row['subject']] = (int)$row['total_no_of_que'];
}

// -------------------------------------------
// STEP 2: Fetch user data
// -------------------------------------------
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit();
}

// If user does not exist, treat progress as zero
if (!$user_data) {
    $user_data = [];
}

// -------------------------------------------
// STEP 3: Build task status
// -------------------------------------------
$task_status = [];
$all_tasks_complete = true;

foreach ($all_activities as $subject => $required_questions) {

    // Convert subject name → column name
    // Example:
    // alphabet_fun → alphabet_current_index
    $column_base = str_replace('_fun', '', $subject);
    $progress_column = $column_base . "_current_index";

    // If user progress is not available, assume 0
    $user_progress = isset($user_data[$progress_column]) ? (int)$user_data[$progress_column] : 0;

    $is_complete = ($user_progress >= $required_questions);

    $task_status[] = [
        "subject" => $subject,
        "required" => $required_questions,
        "progress" => $user_progress,
        "is_complete" => $is_complete
    ];

    if (!$is_complete) {
        $all_tasks_complete = false;
    }
}

// -------------------------------------------
// STEP 4: JSON response
// -------------------------------------------
$response = [
    "success" => true,
    "total_coins" => isset($user_data['coins']) ? (int)$user_data['coins'] : 0,
    "all_tasks_complete" => $all_tasks_complete,
    "tasks" => $task_status
];

echo json_encode($response);
?>
