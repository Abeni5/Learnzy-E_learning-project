<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    echo "Unauthorized";
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$student_id = $_SESSION['user_id'];

$response = [];

foreach ($data['courses'] as $course) {
    $courseName = $course['course_name'];
    $department = $course['department'];
  
    $check = $conn->prepare("SELECT * FROM course_enrollments WHERE student_id = ? AND course_name = ? AND department = ?");
    $check->bind_param("iss", $student_id, $courseName, $department);
    $check->execute();
    $checkResult = $check->get_result();
  
    if ($checkResult->num_rows === 0) {
      $stmt = $conn->prepare("INSERT INTO course_enrollments (student_id, course_name, department, enrollment_date) VALUES (?, ?, ?, CURDATE())");
      $stmt->bind_param("iss", $student_id, $courseName, $department);
      $stmt->execute();
    }
}
  

echo json_encode(["message" => "Courses processed", "details" => $response]);
