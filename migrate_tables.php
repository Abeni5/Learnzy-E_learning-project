<?php
include 'db_connection.php';

// Start transaction
$conn->begin_transaction();

try {
    // Check if student_courses table exists
    $result = $conn->query("SHOW TABLES LIKE 'student_courses'");
    if ($result->num_rows > 0) {
        // Get all data from student_courses
        $result = $conn->query("SELECT * FROM student_courses");
        
        // Insert data into course_enrollments
        while ($row = $result->fetch_assoc()) {
            $stmt = $conn->prepare("INSERT INTO course_enrollments (user_id, course_name, enrollment_date) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $row['student_id'], $row['course_name'], $row['enrollment_date']);
            $stmt->execute();
        }
        
        // Drop the old table
        $conn->query("DROP TABLE student_courses");
        
        echo "Migration completed successfully!";
    } else {
        echo "student_courses table does not exist.";
    }
    
    // Commit transaction
    $conn->commit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo "Error during migration: " . $e->getMessage();
}

$conn->close();
?> 