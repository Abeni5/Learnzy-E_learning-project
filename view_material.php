<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.html");
    exit;
}

$material_id = isset($_GET['material']) ? intval($_GET['material']) : 0;

if ($material_id <= 0) {
    header("Location: student_dashboard.php");
    exit;
}

// Get material details
$stmt = $conn->prepare("
    SELECT m.*, c.title as course_title, c.id as course_id
    FROM course_materials m
    JOIN courses c ON m.course_id = c.id
    WHERE m.id = ?
");
$stmt->bind_param("i", $material_id);
$stmt->execute();
$material = $stmt->get_result()->fetch_assoc();

if (!$material) {
    header("Location: student_dashboard.php");
    exit;
}

// Check if student is enrolled in the course
$stmt = $conn->prepare("
    SELECT 1 FROM course_enrollments 
    WHERE student_id = ? AND course_id = ?
");
$stmt->bind_param("ii", $_SESSION['user_id'], $material['course_id']);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    header("Location: student_dashboard.php");
    exit;
}

// Get next and previous materials
$stmt = $conn->prepare("
    SELECT id, title, material_order
    FROM course_materials
    WHERE course_id = ?
    ORDER BY material_order
");
$stmt->bind_param("i", $material['course_id']);
$stmt->execute();
$all_materials = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$current_index = array_search($material_id, array_column($all_materials, 'id'));
$prev_material = $current_index > 0 ? $all_materials[$current_index - 1] : null;
$next_material = $current_index < count($all_materials) - 1 ? $all_materials[$current_index + 1] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($material['title']) ?> | Learnzy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f4f8;
            margin: 0;
            padding: 30px;
        }
        .material-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .material-info {
            margin-bottom: 30px;
            color: #666;
        }
        .material-content {
            line-height: 1.6;
            color: #2c3e50;
        }
        .material-content h2 {
            color: #2c3e50;
            margin-top: 30px;
            margin-bottom: 15px;
        }
        .material-content p {
            margin-bottom: 15px;
        }
        .material-content ul, .material-content ol {
            margin-bottom: 15px;
            padding-left: 20px;
        }
        .material-content li {
            margin-bottom: 8px;
        }
        .material-content code {
            background: #f8f9fa;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: monospace;
        }
        .material-content pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 15px 0;
        }
        .material-content pre code {
            background: none;
            padding: 0;
        }
        .navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .nav-btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            color: #3498db;
            background: #f8f9fa;
            transition: background-color 0.3s;
        }
        .nav-btn:hover {
            background: #e9ecef;
        }
        .nav-btn i {
            margin: 0 5px;
        }
        .nav-btn.disabled {
            color: #95a5a6;
            cursor: not-allowed;
            background: #f8f9fa;
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
    </style>
</head>
<body>
    <div class="material-container">
        <a href="view_course.php?course=<?= $material['course_id'] ?>" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Course
        </a>
        
        <h1><?= htmlspecialchars($material['title']) ?></h1>
        
        <div class="material-info">
            <p>Course: <?= htmlspecialchars($material['course_title']) ?></p>
            <p><?= htmlspecialchars($material['description']) ?></p>
        </div>

        <div class="material-content">
            <?= nl2br(htmlspecialchars($material['content'])) ?>
        </div>

        <div class="navigation">
            <?php if ($prev_material): ?>
                <a href="view_material.php?material=<?= $prev_material['id'] ?>" class="nav-btn">
                    <i class="fas fa-arrow-left"></i>
                    Previous: <?= htmlspecialchars($prev_material['title']) ?>
                </a>
            <?php else: ?>
                <span class="nav-btn disabled">
                    <i class="fas fa-arrow-left"></i>
                    Previous
                </span>
            <?php endif; ?>

            <?php if ($next_material): ?>
                <a href="view_material.php?material=<?= $next_material['id'] ?>" class="nav-btn">
                    <?= htmlspecialchars($next_material['title']) ?>
                    <i class="fas fa-arrow-right"></i>
                </a>
            <?php else: ?>
                <span class="nav-btn disabled">
                    Next
                    <i class="fas fa-arrow-right"></i>
                </span>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 