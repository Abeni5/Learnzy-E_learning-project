<?php
include 'db_connection.php';

// Function to get course ID by title
function getCourseId($conn, $title) {
    $stmt = $conn->prepare("SELECT id FROM courses WHERE title = ?");
    $stmt->bind_param("s", $title);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['id'];
    }
    return null;
}

// Function to insert course material
function insertMaterial($conn, $course_id, $title, $description, $content, $order) {
    $stmt = $conn->prepare("INSERT INTO course_materials (course_id, title, description, content, material_order) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $course_id, $title, $description, $content, $order);
    return $stmt->execute();
}

// Function to insert quiz
function insertQuiz($conn, $course_id, $title, $description, $passing_score, $order) {
    $stmt = $conn->prepare("INSERT INTO course_quizzes (course_id, title, description, passing_score, quiz_order) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issii", $course_id, $title, $description, $passing_score, $order);
    return $stmt->execute();
}

// Process lectures
$lectures_dir = 'lectures/';
$lecture_files = glob($lectures_dir . '*.pdf');

foreach ($lecture_files as $file) {
    $filename = basename($file, '.pdf');
    $course_title = str_replace('_', ' ', $filename);
    $course_id = getCourseId($conn, $course_title);
    
    if ($course_id) {
        $material_title = $course_title . " Lecture";
        $description = "Main lecture material for " . $course_title;
        $content = $file;
        
        // Check if material already exists
        $stmt = $conn->prepare("SELECT 1 FROM course_materials WHERE course_id = ? AND title = ?");
        $stmt->bind_param("is", $course_id, $material_title);
        $stmt->execute();
        if ($stmt->get_result()->num_rows == 0) {
            insertMaterial($conn, $course_id, $material_title, $description, $content, 1);
            echo "Added material for: " . $course_title . "\n";
        }
    }
}

// Process quizzes
$quizzes_dir = 'quizzes/';
$quiz_files = glob($quizzes_dir . '*_quiz.json');

foreach ($quiz_files as $file) {
    $filename = basename($file, '_quiz.json');
    $course_title = str_replace('_', ' ', $filename);
    $course_id = getCourseId($conn, $course_title);
    
    if ($course_id) {
        $quiz_title = $course_title . " Quiz";
        $description = "Assessment quiz for " . $course_title;
        $passing_score = 60; // Default passing score
        
        // Check if quiz already exists
        $stmt = $conn->prepare("SELECT 1 FROM course_quizzes WHERE course_id = ? AND title = ?");
        $stmt->bind_param("is", $course_id, $quiz_title);
        $stmt->execute();
        if ($stmt->get_result()->num_rows == 0) {
            insertQuiz($conn, $course_id, $quiz_title, $description, $passing_score, 1);
            echo "Added quiz for: " . $course_title . "\n";
        }
    }
}

echo "Import completed!";
?> 