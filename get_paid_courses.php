<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
  http_response_code(403);
  exit;
}

$student_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT c.id as course_id, c.title as course_name 
    FROM course_enrollments ce
    JOIN courses c ON ce.course_id = c.id
    WHERE ce.student_id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$courses = $result->fetch_all(MYSQLI_ASSOC);
echo json_encode($courses);
?>
