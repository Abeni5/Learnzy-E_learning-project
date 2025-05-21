<?php
include 'db_connection.php';

// Get UI/UX Design course ID
$stmt = $conn->prepare("SELECT id FROM courses WHERE title = 'UI/UX Design'");
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    die("UI/UX Design course not found in database");
}

$course_id = $course['id'];

// Check course materials
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM course_materials 
    WHERE course_id = ?
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$materials_count = $stmt->get_result()->fetch_assoc()['count'];

// Check course quizzes
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM course_quizzes 
    WHERE course_id = ?
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$quizzes_count = $stmt->get_result()->fetch_assoc()['count'];

echo "Course Content Status:\n";
echo "Materials: $materials_count\n";
echo "Quizzes: $quizzes_count\n";

// If no materials found, add default material
if ($materials_count == 0) {
    $stmt = $conn->prepare("
        INSERT INTO course_materials (course_id, title, description, content, material_order) 
        VALUES (?, 'UI/UX Design Principles', 'Main course material covering UI/UX design fundamentals', 'lectures/UI_UX_Design.pdf', 1)
    ");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    echo "Added default course material\n";
}

// If no quizzes found, add default quiz
if ($quizzes_count == 0) {
    $stmt = $conn->prepare("
        INSERT INTO course_quizzes (course_id, title, description, passing_score, quiz_order) 
        VALUES (?, 'UI/UX Design Quiz', 'Assessment quiz for UI/UX Design course', 60, 1)
    ");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    echo "Added default course quiz\n";
}

// Update course paths
$stmt = $conn->prepare("
    UPDATE courses 
    SET pdf_path = 'lectures/UI_UX_Design.pdf',
        quiz_path = 'quizzes/uiux_quiz.json'
    WHERE id = ?
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
echo "Updated course paths\n";

echo "Verification complete!\n";
?> 