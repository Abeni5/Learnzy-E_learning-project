<?php
session_start();
include 'db_connection.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['status' => 'error', 'message' => 'Please login as a student to enroll in courses']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['courses']) || empty($data['courses'])) {
    echo json_encode(['status' => 'error', 'message' => 'No courses selected']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    $student_id = $_SESSION['user_id'];
    $enrollment_date = date('Y-m-d H:i:s');
    $total_amount = 0;
    $enrolled_courses = [];

    // Process each selected course
    foreach ($data['courses'] as $course) {
        // Get course price from database
        $stmt = $conn->prepare("SELECT id, title, price FROM courses WHERE title = ?");
        $stmt->bind_param("s", $course['course_name']);
        $stmt->execute();
        $result = $stmt->get_result();
        $course_data = $result->fetch_assoc();
        
        if (!$course_data) {
            throw new Exception("Course not found: " . $course['course_name']);
        }

        $course_price = $course_data['price'];
        $total_amount += $course_price;

        // Check if student is already enrolled
        $stmt = $conn->prepare("SELECT * FROM course_enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $student_id, $course_data['id']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Already enrolled in: " . $course_data['title']);
        }

        // Insert enrollment record
        $stmt = $conn->prepare("INSERT INTO course_enrollments (student_id, course_id, enrollment_date) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $student_id, $course_data['id'], $enrollment_date);
        $stmt->execute();
        
        $enrolled_courses[] = [
            'course_name' => $course_data['title'],
            'price' => $course_price
        ];
    }

    // Process payment (default to cash)
    $payment_date = date('Y-m-d H:i:s');
    
    // Insert payment record
    $stmt = $conn->prepare("INSERT INTO payments (user_id, amount, payment_date, payment_method, status) VALUES (?, ?, ?, 'cash', 'completed')");
    $stmt->bind_param("ids", $student_id, $total_amount, $payment_date);
    $stmt->execute();
    
    $payment_id = $conn->insert_id;

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Successfully enrolled in courses',
        'data' => [
            'payment_id' => $payment_id,
            'total_amount' => $total_amount,
            'enrolled_courses' => $enrolled_courses,
            'payment_date' => $payment_date
        ]
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
