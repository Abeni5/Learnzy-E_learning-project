<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate
if (!isset($data['course'], $data['score'], $data['total'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

$course = $conn->real_escape_string($data['course']);
$score = intval($data['score']);
$total = intval($data['total']);
$user_id = $_SESSION['user_id'];

// Insert into DB
$stmt = $conn->prepare("INSERT INTO quiz_scores (user_id, course_name, score, total_questions, date_taken) VALUES (?, ?, ?, ?, NOW())");
$stmt->bind_param("isii", $user_id, $course, $score, $total);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Score saved successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save score.']);
}
?>


