<?php
session_start();
include 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

// Get quiz ID from URL
$quiz_id = isset($_GET['quiz']) ? $_GET['quiz'] : 0;

if (empty($quiz_id)) {
    header("Location: student_dashboard.php");
    exit;
}

// Check if this is a temporary quiz ID
if (is_string($quiz_id) && strpos($quiz_id, 'temp_') === 0) {
    $course_id = substr($quiz_id, 5); // Remove 'temp_' prefix
    // Get course details
    $stmt = $conn->prepare("SELECT title FROM courses WHERE id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();
    
    if (!$course) {
        header("Location: student_dashboard.php");
        exit;
    }
    
    $quiz = [
        'id' => $quiz_id,
        'course_id' => $course_id,
        'title' => $course['title'] . " Quiz",
        'course_title' => $course['title']
    ];
} else {
    // Get quiz details from course_quizzes table
    $stmt = $conn->prepare("
        SELECT q.*, c.title as course_title 
        FROM course_quizzes q 
        JOIN courses c ON q.course_id = c.id 
        WHERE q.id = ?
    ");
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $quiz = $stmt->get_result()->fetch_assoc();
}

if (!$quiz) {
    header("Location: student_dashboard.php");
    exit;
}

// Get the quiz JSON file
$course_title = strtolower(str_replace([' ', '/'], ['_', ''], $quiz['course_title']));

// Special case mapping for course titles
$quiz_file_mapping = [
    'uiux_design' => 'uiux',
    'web_development' => 'webdev'
];

$quiz_file = 'quizzes/' . ($quiz_file_mapping[$course_title] ?? $course_title) . '_quiz.json';

// Debug information
error_log("Course Title: " . $course_title);
error_log("Quiz File: " . $quiz_file);
error_log("File exists: " . (file_exists($quiz_file) ? 'Yes' : 'No'));

if (!file_exists($quiz_file)) {
    die("Error: Quiz file not found for this course. Looking for: " . $quiz_file);
}

$quiz_data = json_decode(file_get_contents($quiz_file), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("Error: Invalid quiz file format. Error: " . json_last_error_msg());
}

// Debug information
error_log("Quiz Data: " . print_r($quiz_data, true));

// Validate quiz data structure
if (!isset($quiz_data['course_title'], $quiz_data['total_questions'], $quiz_data['passing_score'], $quiz_data['questions']) ||
    !is_array($quiz_data['questions'])) {
    die("Error: Invalid quiz data structure.");
}

// Validate each question
foreach ($quiz_data['questions'] as $question) {
    if (!isset($question['id'], $question['question'], $question['options'], $question['correct_answer']) ||
        !is_array($question['options']) ||
        !isset($question['options'][$question['correct_answer']])) {
        die("Error: Invalid question structure in quiz.");
    }
}

// Handle quiz submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score = 0;
    $total_questions = count($quiz_data['questions']);
    $answers = [];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First, ensure all questions exist in the database
        $stmt = $conn->prepare("
            INSERT IGNORE INTO quiz_questions (quiz_id, question_text, question_order) 
            VALUES (?, ?, ?)
        ");
        
        foreach ($quiz_data['questions'] as $index => $question) {
            $stmt->bind_param("isi", $quiz_id, $question['question'], $index);
            $stmt->execute();
            
            // Get the question ID (either newly inserted or existing)
            $question_id = $conn->insert_id ?: $conn->query("
                SELECT id FROM quiz_questions 
                WHERE quiz_id = $quiz_id AND question_text = '" . 
                $conn->real_escape_string($question['question']) . "'"
            )->fetch_assoc()['id'];
            
            // Save options for this question
            $option_stmt = $conn->prepare("
                INSERT IGNORE INTO quiz_options (question_id, option_text, is_correct, option_order) 
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($question['options'] as $opt_index => $option_text) {
                $is_correct = ($opt_index === $question['correct_answer']);
                $option_stmt->bind_param("isii", $question_id, $option_text, $is_correct, $opt_index);
                $option_stmt->execute();
            }
            
            // Get the selected answer
            $selected_answer = isset($_POST['question_' . $question['id']]) ? intval($_POST['question_' . $question['id']]) : -1;
            $is_correct = ($selected_answer === $question['correct_answer']);
            if ($is_correct) {
                $score++;
            }
            
            // Get the option ID for the selected answer
            $option_id = $conn->query("
                SELECT id FROM quiz_options 
                WHERE question_id = $question_id AND option_order = $selected_answer
            ")->fetch_assoc()['id'];
            
            $answers[] = [
                'question_id' => $question_id,
                'selected_option_id' => $option_id
            ];
        }
        
        $percentage = ($score / $total_questions) * 100;
        
        // Save attempt to database
        $stmt = $conn->prepare("
            INSERT INTO student_quiz_attempts (student_id, quiz_id, score, completed_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param("iid", $_SESSION['user_id'], $quiz_id, $percentage);
        $stmt->execute();
        $attempt_id = $conn->insert_id;
        
        // Save individual answers
        $stmt = $conn->prepare("
            INSERT INTO student_quiz_answers (attempt_id, question_id, selected_option_id) 
            VALUES (?, ?, ?)
        ");
        
        foreach ($answers as $answer) {
            $stmt->bind_param("iii", 
                $attempt_id, 
                $answer['question_id'], 
                $answer['selected_option_id']
            );
            $stmt->execute();
        }
        
        $conn->commit();
        
        // Redirect to quiz results page
        header("Location: quiz_results.php?attempt=" . $attempt_id);
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        die("Error saving quiz results: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($quiz['title']) ?> | Learnzy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f4f8;
            margin: 0;
            padding: 30px;
        }
        .quiz-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        .quiz-header {
            margin-bottom: 30px;
        }
        .quiz-title {
            color: #2c3e50;
            margin: 0 0 10px 0;
            font-size: 2em;
        }
        .quiz-info {
            color: #666;
            margin-bottom: 20px;
        }
        .question {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .question-text {
            color: #2c3e50;
            margin: 0 0 15px 0;
            font-size: 1.2em;
        }
        .options {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .option {
            margin-bottom: 10px;
        }
        .option label {
            display: block;
            padding: 10px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .option label:hover {
            background: #e9ecef;
        }
        .option input[type="radio"] {
            margin-right: 10px;
        }
        .submit-btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            transition: background 0.3s;
        }
        .submit-btn:hover {
            background: #2980b9;
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
    <div class="quiz-container">
        <a href="view_course.php?course=<?= $quiz['course_id'] ?>" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Course
        </a>

        <div class="quiz-header">
            <h1 class="quiz-title"><?= htmlspecialchars($quiz['title']) ?></h1>
            <div class="quiz-info">
                <p><strong>Course:</strong> <?= htmlspecialchars($quiz['course_title']) ?></p>
                <p><strong>Description:</strong> <?= htmlspecialchars($quiz['description']) ?></p>
                <p><strong>Passing Score:</strong> <?= $quiz['passing_score'] ?>%</p>
            </div>
        </div>

        <form method="POST">
            <?php foreach ($quiz_data['questions'] as $question): ?>
                <div class="question">
                    <h3 class="question-text"><?= htmlspecialchars($question['question']) ?></h3>
                    <ul class="options">
                        <?php foreach ($question['options'] as $index => $option): ?>
                            <li class="option">
                                <label>
                                    <input type="radio" name="question_<?= $question['id'] ?>" value="<?= $index ?>" required>
                                    <?= htmlspecialchars($option) ?>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="submit-btn">Submit Quiz</button>
        </form>
    </div>
</body>
</html> 