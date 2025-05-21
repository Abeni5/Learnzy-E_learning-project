<?php
session_start();
include 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

// Get course ID from URL
$course_id = isset($_GET['course']) ? intval($_GET['course']) : 0;

if ($course_id <= 0) {
    header("Location: student_dashboard.php");
    exit;
}

// Get course details
$stmt = $conn->prepare("
    SELECT c.*, 
           (SELECT COUNT(*) FROM course_materials WHERE course_id = c.id) as material_count,
           (SELECT COUNT(*) FROM course_quizzes WHERE course_id = c.id) as quiz_count
    FROM courses c 
    WHERE c.id = ?
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    header("Location: student_dashboard.php");
    exit;
}

// Check if student is enrolled
$stmt = $conn->prepare("
    SELECT 1 FROM course_enrollments 
    WHERE student_id = ? AND course_id = ?
");
$stmt->bind_param("ii", $_SESSION['user_id'], $course_id);
$stmt->execute();
$is_enrolled = $stmt->get_result()->num_rows > 0;

// Get course materials
$stmt = $conn->prepare("
    SELECT * FROM course_materials 
    WHERE course_id = ? 
    ORDER BY material_order
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$materials = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get course quizzes
$stmt = $conn->prepare("
    SELECT q.*, 
           (SELECT score FROM student_quiz_attempts 
            WHERE quiz_id = q.id AND student_id = ? 
            ORDER BY completed_at DESC LIMIT 1) as last_score
    FROM course_quizzes q 
    WHERE q.course_id = ? 
    ORDER BY quiz_order
");
$stmt->bind_param("ii", $_SESSION['user_id'], $course_id);
$stmt->execute();
$quizzes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Debug information
error_log("Course ID: " . $course_id);
error_log("Quizzes found: " . count($quizzes));
error_log("Quizzes data: " . print_r($quizzes, true));

// If no materials or quizzes found, try to get from the course's pdf_path and quiz_path
if (empty($materials) && !empty($course['pdf_path'])) {
    $materials[] = [
        'title' => 'Course Material',
        'description' => 'Main course material',
        'content' => $course['pdf_path']
    ];
}

if (empty($quizzes) && !empty($course['quiz_path'])) {
    $quizzes[] = [
        'id' => 'temp_' . $course_id,
        'title' => 'Course Quiz',
        'description' => 'Main course quiz',
        'passing_score' => 60
    ];
    error_log("Created temporary quiz with ID: temp_" . $course_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($course['title']) ?> | Learnzy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f4f8;
            margin: 0;
            padding: 30px;
        }
        .course-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .course-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .course-title {
            color: #2c3e50;
            margin: 0 0 15px 0;
            font-size: 2em;
        }
        .course-info {
            color: #666;
            margin-bottom: 20px;
        }
        .course-stats {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        .stat-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            flex: 1;
        }
        .stat-number {
            font-size: 1.5em;
            font-weight: bold;
            color: #3498db;
        }
        .stat-label {
            color: #666;
            font-size: 0.9em;
        }
        .materials-section, .quizzes-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .section-title {
            color: #2c3e50;
            margin: 0 0 20px 0;
            font-size: 1.5em;
        }
        .material-item, .quiz-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid #3498db;
        }
        .material-title, .quiz-title {
            color: #2c3e50;
            margin: 0 0 10px 0;
            font-size: 1.2em;
        }
        .material-description, .quiz-description {
            color: #666;
            margin-bottom: 15px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            background: #3498db;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #2980b9;
        }
        .btn-success {
            background: #2ecc71;
        }
        .btn-success:hover {
            background: #27ae60;
        }
        .btn-warning {
            background: #f1c40f;
        }
        .btn-warning:hover {
            background: #f39c12;
        }
        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            color: #3498db;
            text-decoration: none;
        }
        .back-btn:hover {
            text-decoration: underline;
        }
        .not-enrolled {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="course-container">
        <a href="student_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="course-header">
            <h1 class="course-title"><?= htmlspecialchars($course['title']) ?></h1>
            <div class="course-info">
                <p><strong>Category:</strong> <?= htmlspecialchars($course['category']) ?></p>
                <p><strong>Description:</strong> <?= htmlspecialchars($course['description']) ?></p>
                <p><strong>Requirements:</strong> <?= htmlspecialchars($course['requirements']) ?></p>
            </div>
            <div class="course-stats">
                <div class="stat-box">
                    <div class="stat-number"><?= $course['material_count'] ?></div>
                    <div class="stat-label">Course Materials</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= $course['quiz_count'] ?></div>
                    <div class="stat-label">Quizzes</div>
                </div>
            </div>
        </div>

        <?php if (!$is_enrolled): ?>
            <div class="not-enrolled">
                <h3>Not Enrolled</h3>
                <p>You need to enroll in this course to access the materials and quizzes.</p>
                <a href="enroll.php?course=<?= $course_id ?>" class="btn">Enroll Now</a>
            </div>
        <?php else: ?>
            <div class="materials-section">
                <h2 class="section-title">Course Materials</h2>
                <?php if (empty($materials)): ?>
                    <p>No course materials available yet.</p>
                <?php else: ?>
                    <?php foreach ($materials as $material): ?>
                        <div class="material-item">
                            <h3 class="material-title"><?= htmlspecialchars($material['title']) ?></h3>
                            <p class="material-description"><?= htmlspecialchars($material['description']) ?></p>
                            <a href="<?= htmlspecialchars($material['content']) ?>" class="btn" target="_blank">
                                View Material
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="quizzes-section">
                <h2 class="section-title">Course Quizzes</h2>
                <?php if (empty($quizzes)): ?>
                    <p>No quizzes available yet.</p>
                <?php else: ?>
                    <?php foreach ($quizzes as $quiz): ?>
                        <div class="quiz-item">
                            <h3 class="quiz-title"><?= htmlspecialchars($quiz['title']) ?></h3>
                            <p class="quiz-description"><?= htmlspecialchars($quiz['description']) ?></p>
                            <?php if (isset($quiz['last_score']) && $quiz['last_score'] !== null): ?>
                                <p>Last Score: <?= number_format($quiz['last_score'], 1) ?>%</p>
                                <?php if ($quiz['last_score'] < $quiz['passing_score']): ?>
                                    <a href="take_quiz.php?quiz=<?= strpos($quiz['id'], 'temp_') === 0 ? $course_id : $quiz['id'] ?>" class="btn btn-warning">
                                        Retake Quiz
                                    </a>
                                <?php else: ?>
                                    <span class="btn btn-success">Passed!</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="take_quiz.php?quiz=<?= strpos($quiz['id'], 'temp_') === 0 ? $course_id : $quiz['id'] ?>" class="btn">
                                    Take Quiz
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 