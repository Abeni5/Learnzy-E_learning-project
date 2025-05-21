<?php
include 'db_connection.php';

try {
    // Check total number of courses
    $result = $conn->query("SELECT COUNT(*) as total FROM courses");
    $total = $result->fetch_assoc()['total'];
    
    echo "<h2>Database Check Results:</h2>";
    echo "<p>Total courses in database: " . $total . "</p>";
    
    if ($total > 0) {
        // Show sample of courses
        $result = $conn->query("SELECT id, title, category FROM courses LIMIT 5");
        echo "<h3>Sample Courses:</h3>";
        echo "<ul>";
        while ($row = $result->fetch_assoc()) {
            echo "<li>ID: " . $row['id'] . " - " . htmlspecialchars($row['title']) . " (" . htmlspecialchars($row['category']) . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>No courses found in the database!</p>";
    }
    
    // Check if tables exist
    $tables = ['courses', 'course_enrollments', 'course_materials', 'course_quizzes'];
    echo "<h3>Table Check:</h3>";
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        echo "<p>Table '$table': " . ($result->num_rows > 0 ? "✅ Exists" : "❌ Missing") . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

$conn->close();
?> 