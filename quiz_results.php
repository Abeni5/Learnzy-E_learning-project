<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.html");
    exit;
}

$attempt_id = isset($_GET['attempt']) ? intval($_GET['attempt']) : 0;

if ($attempt_id <= 0) {
    header("Location: student_dashboard.php");
    exit;
}

// Get attempt details
$stmt = $conn->prepare("
    SELECT a.*, q.title as quiz_title, q.description as quiz_description,
           q.passing_score, c.title as course_title, c.id as course_id
    FROM student_quiz_attempts a
    JOIN course_quizzes q ON a.quiz_id = q.id
    JOIN courses c ON q.course_id = c.id
    WHERE a.id = ? AND a.student_id = ?
");
$stmt->bind_param("ii", $attempt_id, $_SESSION['user_id']);
$stmt->execute();
$attempt = $stmt->get_result()->fetch_assoc();

if (!$attempt) {
    header("Location: student_dashboard.php");
    exit;
}

// Get questions and answers
$stmt = $conn->prepare("
    SELECT q.question_text, q.question_order,
           o.option_text as selected_option,
           o.is_correct as is_selected_correct,
           (
               SELECT option_text 
               FROM quiz_options 
               WHERE question_id = q.id AND is_correct = 1
           ) as correct_option
    FROM student_quiz_answers a
    JOIN quiz_questions q ON a.question_id = q.id
    JOIN quiz_options o ON a.selected_option_id = o.id
    WHERE a.attempt_id = ?
    ORDER BY q.question_order
");
$stmt->bind_param("i", $attempt_id);
$stmt->execute();
$answers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$passed = $attempt['score'] >= $attempt['passing_score'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quiz Results | Learnzy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f4f8;
            margin: 0;
            padding: 30px;
        }
        .results-container {
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
        .quiz-info {
            margin-bottom: 30px;
            color: #666;
        }
        .score-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .score {
            font-size: 2.5em;
            font-weight: bold;
            color: <?= $passed ? '#27ae60' : '#e74c3c' ?>;
        }
        .status {
            font-size: 1.2em;
            color: <?= $passed ? '#27ae60' : '#e74c3c' ?>;
            margin-top: 10px;
        }
        .question {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }
        .question h3 {
            margin: 0 0 15px 0;
            color: #2c3e50;
        }
        .answer {
            margin: 10px 0;
            padding: 10px;
            border-radius: 4px;
        }
        .correct {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .incorrect {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
        .actions {
            margin-top: 30px;
            text-align: center;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin: 0 10px;
            transition: background-color 0.3s;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        .btn-secondary:hover {
            background: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="results-container">
        <a href="view_course.php?course=<?= $attempt['course_id'] ?>" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Course
        </a>
        
        <h1>Quiz Results</h1>
        
        <div class="quiz-info">
            <p>Course: <?= htmlspecialchars($attempt['course_title']) ?></p>
            <p>Quiz: <?= htmlspecialchars($attempt['quiz_title']) ?></p>
            <p><?= htmlspecialchars($attempt['quiz_description']) ?></p>
        </div>

        <div class="score-section">
            <div class="score"><?= number_format($attempt['score'], 1) ?>%</div>
            <div class="status">
                <?= $passed ? 'Passed!' : 'Not Passed' ?>
            </div>
            <p>Passing Score: <?= $attempt['passing_score'] ?>%</p>
        </div>

        <h2>Question Review</h2>
        <?php foreach ($answers as $index => $answer): ?>
            <div class="question">
                <h3>Question <?= $index + 1 ?>: <?= htmlspecialchars($answer['question_text']) ?></h3>
                <div class="answer <?= $answer['is_selected_correct'] ? 'correct' : 'incorrect' ?>">
                    <strong>Your Answer:</strong> <?= htmlspecialchars($answer['selected_option']) ?>
                </div>
                <?php if (!$answer['is_selected_correct']): ?>
                    <div class="answer correct">
                        <strong>Correct Answer:</strong> <?= htmlspecialchars($answer['correct_option']) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="actions">
            <a href="view_course.php?course=<?= $attempt['course_id'] ?>" class="btn btn-primary">
                Return to Course
            </a>
            <?php if (!$passed): ?>
                <a href="take_quiz.php?quiz=<?= $attempt['quiz_id'] ?>" class="btn btn-secondary">
                    Retake Quiz
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 