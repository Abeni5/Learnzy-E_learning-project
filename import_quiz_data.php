<?php
include 'db_connection.php';

// Function to get all quiz JSON files
function getQuizFiles() {
    $quiz_files = [];
    $quiz_dir = 'quizzes/';
    
    if (is_dir($quiz_dir)) {
        $files = scandir($quiz_dir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                $quiz_files[] = $quiz_dir . $file;
            }
        }
    }
    
    return $quiz_files;
}

// Function to get course ID from quiz file name
function getCourseIdFromQuizFile($conn, $quiz_file) {
    $course_title = basename($quiz_file, '_quiz.json');
    
    // Special case mapping for course titles
    $quiz_file_mapping = [
        'uiux' => 'UI/UX Design',
        'webdev' => 'Web Development',
        'programming' => 'Programming',
        'algebra' => 'Algebra',
        'calculus' => 'Calculus',
        'statistics' => 'Statistics',
        'finance' => 'Finance',
        'entrepreneurship' => 'Entrepreneurship',
        'graphic_design' => 'Graphic Design',
        'motion_graphics' => 'Motion Graphics',
        'marketing' => 'Marketing'
    ];
    
    $course_title = $quiz_file_mapping[$course_title] ?? str_replace('_', ' ', $course_title);
    
    // Debug output
    echo "Looking for course: $course_title<br>";
    
    $stmt = $conn->prepare("SELECT id FROM courses WHERE title = ?");
    $stmt->bind_param("s", $course_title);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo "Found course ID: " . $row['id'] . "<br>";
        return $row['id'];
    }
    
    echo "Course not found in database<br>";
    return null;
}

// Start transaction
$conn->begin_transaction();

try {
    $quiz_files = getQuizFiles();
    $imported_count = 0;
    
    echo "Found " . count($quiz_files) . " quiz files to process<br><br>";
    
    foreach ($quiz_files as $quiz_file) {
        echo "<hr>Processing: $quiz_file<br>";
        
        // Get course ID
        $course_id = getCourseIdFromQuizFile($conn, $quiz_file);
        if (!$course_id) {
            echo "Course not found for quiz file: $quiz_file<br>";
            continue;
        }
        
        // Read quiz data
        $quiz_data = json_decode(file_get_contents($quiz_file), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Error reading quiz file: $quiz_file<br>";
            continue;
        }
        
        echo "Quiz data loaded successfully<br>";
        echo "Course title: " . $quiz_data['course_title'] . "<br>";
        echo "Total questions: " . count($quiz_data['questions']) . "<br>";
        
        // Create quiz if it doesn't exist
        $stmt = $conn->prepare("
            INSERT IGNORE INTO course_quizzes (course_id, title, description, passing_score) 
            VALUES (?, ?, ?, ?)
        ");
        $quiz_title = $quiz_data['course_title'] . " Quiz";
        $description = "Quiz for " . $quiz_data['course_title'];
        $passing_score = $quiz_data['passing_score'];
        $stmt->bind_param("issi", $course_id, $quiz_title, $description, $passing_score);
        $stmt->execute();
        
        echo "Quiz entry created/updated<br>";
        
        // Get quiz ID
        $quiz_id = $conn->insert_id ?: $conn->query("
            SELECT id FROM course_quizzes 
            WHERE course_id = $course_id AND title = '" . 
            $conn->real_escape_string($quiz_title) . "'"
        )->fetch_assoc()['id'];
        
        echo "Quiz ID: $quiz_id<br>";
        
        // Import questions and options
        foreach ($quiz_data['questions'] as $index => $question) {
            echo "Processing question " . ($index + 1) . "<br>";
            
            // Insert question
            $stmt = $conn->prepare("
                INSERT IGNORE INTO quiz_questions (quiz_id, question_text, question_order) 
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("isi", $quiz_id, $question['question'], $index);
            $stmt->execute();
            
            // Get question ID
            $question_id = $conn->insert_id ?: $conn->query("
                SELECT id FROM quiz_questions 
                WHERE quiz_id = $quiz_id AND question_text = '" . 
                $conn->real_escape_string($question['question']) . "'"
            )->fetch_assoc()['id'];
            
            echo "Question ID: $question_id<br>";
            
            // Insert options
            $option_stmt = $conn->prepare("
                INSERT IGNORE INTO quiz_options (question_id, option_text, is_correct, option_order) 
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($question['options'] as $opt_index => $option_text) {
                $is_correct = ($opt_index === $question['correct_answer']);
                $option_stmt->bind_param("isii", $question_id, $option_text, $is_correct, $opt_index);
                $option_stmt->execute();
            }
            
            echo "Options imported<br>";
            $imported_count++;
        }
        
        echo "Completed importing quiz for course ID: $course_id<br>";
    }
    
    $conn->commit();
    echo "<br>Successfully imported $imported_count questions!<br>";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "Error importing quiz data: " . $e->getMessage();
}

$conn->close();
?>