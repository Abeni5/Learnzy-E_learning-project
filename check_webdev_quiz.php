<?php
include 'db_connection.php';

// Start transaction
$conn->begin_transaction();

try {
    // Get Web Development course ID
    $stmt = $conn->prepare("SELECT id FROM courses WHERE title LIKE '%Web Development%'");
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();

    if (!$course) {
        throw new Exception("Web Development course not found in database");
    }

    $course_id = $course['id'];
    echo "Web Development course ID: " . $course_id . "<br>";

    // Delete any existing quizzes for this course
    $stmt = $conn->prepare("DELETE FROM course_quizzes WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    echo "Cleared existing quizzes for Web Development<br>";

    // Insert new quiz
    $stmt = $conn->prepare("
        INSERT INTO course_quizzes (course_id, title, description, passing_score, quiz_order) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $title = "Web Development Quiz";
    $description = "Test your knowledge of Web Development";
    $passing_score = 60;
    $quiz_order = 1;
    
    $stmt->bind_param("issii", $course_id, $title, $description, $passing_score, $quiz_order);
    
    if (!$stmt->execute()) {
        throw new Exception("Error inserting quiz: " . $stmt->error);
    }
    
    $quiz_id = $conn->insert_id;
    echo "Successfully inserted Web Development quiz with ID: " . $quiz_id . "<br>";

    $conn->commit();
    echo "<br>All operations completed successfully!<br>";
    echo "Please refresh the Web Development course page to see the changes.";

} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}

$conn->close();
?> 