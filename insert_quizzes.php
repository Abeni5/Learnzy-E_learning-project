<?php
include 'db_connection.php';

// Get all courses
$courses = $conn->query("SELECT id, title FROM courses");

// Quiz files mapping
$quiz_files = [
    'uiux_quiz.json' => 'UI/UX Design',
    'webdev_quiz.json' => 'Web Development',
    'programming_quiz.json' => 'Programming',
    'algebra_quiz.json' => 'Algebra',
    'calculus_quiz.json' => 'Calculus',
    'statistics_quiz.json' => 'Statistics',
    'finance_quiz.json' => 'Finance',
    'marketing_quiz.json' => 'Marketing',
    'entrepreneurship_quiz.json' => 'Entrepreneurship',
    'graphic_design_quiz.json' => 'Graphic Design',
    'motion_graphics_quiz.json' => 'Motion Graphics'
];

// Start transaction
$conn->begin_transaction();

try {
    // Get existing quizzes
    $existing_quizzes = $conn->query("SELECT course_id FROM course_quizzes");
    $existing_course_ids = [];
    while ($row = $existing_quizzes->fetch_assoc()) {
        $existing_course_ids[] = $row['course_id'];
    }
    
    // Insert new quizzes
    $stmt = $conn->prepare("
        INSERT INTO course_quizzes (course_id, title, description, passing_score, quiz_order) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($courses as $course) {
        // Skip if course already has a quiz
        if (in_array($course['id'], $existing_course_ids)) {
            continue;
        }
        
        foreach ($quiz_files as $quiz_file => $quiz_title) {
            // Special case for Web Development
            if ($quiz_title === 'Web Development' && stripos($course['title'], 'Web Development') !== false) {
                $title = $course['title'] . " Quiz";
                $description = "Test your knowledge of " . $course['title'];
                $passing_score = 60;
                $quiz_order = 1;
                
                $stmt->bind_param("issii", 
                    $course['id'],
                    $title,
                    $description,
                    $passing_score,
                    $quiz_order
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Error inserting quiz for course: " . $course['title']);
                }
                break;
            }
            // Handle other courses
            else if (stripos($course['title'], $quiz_title) !== false) {
                $title = $course['title'] . " Quiz";
                $description = "Test your knowledge of " . $course['title'];
                $passing_score = 60;
                $quiz_order = 1;
                
                $stmt->bind_param("issii", 
                    $course['id'],
                    $title,
                    $description,
                    $passing_score,
                    $quiz_order
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Error inserting quiz for course: " . $course['title']);
                }
            }
        }
    }
    
    $conn->commit();
    echo "Successfully inserted remaining quizzes into the database.";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}

$stmt->close();
$conn->close();
?> 