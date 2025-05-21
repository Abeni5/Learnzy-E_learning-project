<?php
// Connect to database
$conn = new mysqli("localhost", "root", "", "elearning_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get category from query parameter
$category = isset($_GET['category']) ? $_GET['category'] : '';

if (!empty($category)) {
    // Prepare and execute query
    $stmt = $conn->prepare("SELECT title, price FROM courses WHERE category = ? ORDER BY title");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch all courses
    $courses = array();
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($courses);
} else {
    // Return empty array if no category provided
    header('Content-Type: application/json');
    echo json_encode([]);
}

$stmt->close();
$conn->close();
?> 