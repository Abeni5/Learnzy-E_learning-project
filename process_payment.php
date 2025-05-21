<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get the POST data
$input = json_decode(file_get_contents('php://input'), true);
$paymentMethod = $input['paymentMethod'];
$courses = $input['courses'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Calculate total amount
    $totalAmount = 0;
    foreach ($courses as $course) {
        // Extract price from course name (assuming format: "Course Name 💲200")
        preg_match('/💲(\d+)/', $course['course_name'], $matches);
        if (isset($matches[1])) {
            $totalAmount += intval($matches[1]);
        }
    }

    $student_id = $_SESSION['user_id'];
    
    // Check for existing enrollments
    $existingEnrollments = [];
    foreach ($courses as $course) {
        // Extract clean course name (remove price)
        $courseName = preg_replace('/\s*💲\d+\s*$/', '', $course['course_name']);
        
        // First get the course ID from the courses table
        $stmt = $conn->prepare("SELECT id, title FROM courses WHERE title = ?");
        $stmt->bind_param("s", $courseName);
        $stmt->execute();
        $result = $stmt->get_result();
        $courseData = $result->fetch_assoc();
        
        if (!$courseData) {
            throw new Exception("Course not found: " . $courseName);
        }
        
        $check = $conn->prepare("SELECT course_id FROM course_enrollments WHERE student_id = ? AND course_id = ?");
        $check->bind_param("ii", $student_id, $courseData['id']);
        $check->execute();
        $result = $check->get_result();
        if ($result->num_rows > 0) {
            $existingEnrollments[] = $courseData['title'];
        }
    }

    if (!empty($existingEnrollments)) {
        throw new Exception("You are already enrolled in: " . implode(", ", $existingEnrollments));
    }

    // Create payment record
    $stmt = $conn->prepare("INSERT INTO payments (user_id, amount, payment_method) VALUES (?, ?, ?)");
    $stmt->bind_param("ids", $student_id, $totalAmount, $paymentMethod);
    $stmt->execute();
    $payment_id = $conn->insert_id;

    // Enroll in courses
    foreach ($courses as $course) {
        // Extract clean course name (remove price)
        $courseName = preg_replace('/\s*💲\d+\s*$/', '', $course['course_name']);
        
        // Get course data
        $stmt = $conn->prepare("SELECT id, title FROM courses WHERE title = ?");
        $stmt->bind_param("s", $courseName);
        $stmt->execute();
        $result = $stmt->get_result();
        $courseData = $result->fetch_assoc();
        
        if (!$courseData) {
            throw new Exception("Course not found: " . $courseName);
        }
        
        $department = $course['department'];
        
        $stmt = $conn->prepare("INSERT INTO course_enrollments (student_id, course_id, department, payment_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iisi", $student_id, $courseData['id'], $department, $payment_id);
        $stmt->execute();
    }

    // Generate payment instructions based on method
    $instructions = '';
    switch ($paymentMethod) {
        case 'cash':
            $instructions = "Please visit our office to complete the payment of 💲$totalAmount";
            break;
        case 'bank':
            $instructions = "Please transfer 💲$totalAmount to our bank account: Bank Name - 1234567890";
            break;
        case 'mobile':
            $instructions = "Please send 💲$totalAmount to our mobile money number: 1234567890";
            break;
    }

    // Commit transaction
    $conn->commit();

    // Prepare success response
    $response = [
        'success' => true,
        'message' => 'Courses enrolled successfully!',
        'payment_id' => $payment_id,
        'instructions' => $instructions,
        'amount' => $totalAmount,
        'courses' => array_map(function($course) {
            return $course['course_name'];
        }, $courses)
    ];

    echo json_encode($response);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 