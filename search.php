<?php
session_start();
include 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$q = $_GET['q'] ?? '';
$searchResults = [];
$error = null;

if (!empty($q)) {
    try {
        // Search in courses table with enrollment status
        $stmt = $conn->prepare("
            SELECT c.*, 
                   COALESCE((SELECT COUNT(*) FROM course_enrollments WHERE course_id = c.id AND student_id = ?), 0) as is_enrolled,
                   COALESCE((SELECT COUNT(*) FROM course_materials WHERE course_id = c.id), 0) as material_count,
                   COALESCE((SELECT COUNT(*) FROM course_quizzes WHERE course_id = c.id), 0) as quiz_count
            FROM courses c 
            WHERE LOWER(c.title) LIKE LOWER(?) 
               OR LOWER(c.description) LIKE LOWER(?) 
               OR LOWER(c.category) LIKE LOWER(?)
            ORDER BY c.category, c.title
        ");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $searchTerm = "%{$q}%";
        if (!$stmt->bind_param("isss", $_SESSION['user_id'], $searchTerm, $searchTerm, $searchTerm)) {
            throw new Exception("Binding parameters failed: " . $stmt->error);
        }

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception("Getting result failed: " . $stmt->error);
        }

        $searchResults = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } catch (Exception $e) {
        $error = "Search error: " . $e->getMessage();
        error_log("Search error in search.php: " . $e->getMessage());
    }
}

// Debug information
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    echo "<pre>";
    echo "Session user_id: " . ($_SESSION['user_id'] ?? 'not set') . "\n";
    echo "Search term: " . htmlspecialchars($q) . "\n";
    echo "Number of results: " . count($searchResults) . "\n";
    echo "Error: " . ($error ?? 'none') . "\n";
    echo "SQL Query: SELECT c.*, COALESCE((SELECT COUNT(*) FROM course_enrollments WHERE course_id = c.id AND student_id = " . ($_SESSION['user_id'] ?? 'NULL') . "), 0) as is_enrolled, COALESCE((SELECT COUNT(*) FROM course_materials WHERE course_id = c.id), 0) as material_count, COALESCE((SELECT COUNT(*) FROM course_quizzes WHERE course_id = c.id), 0) as quiz_count FROM courses c WHERE LOWER(c.title) LIKE LOWER('%{$q}%') OR LOWER(c.description) LIKE LOWER('%{$q}%') OR LOWER(c.category) LIKE LOWER('%{$q}%') ORDER BY c.category, c.title\n";
    echo "</pre>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Results - Learnzy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('image/background.png') no-repeat center center fixed;
            background-size: cover;
            color: white;
        }
        .overlay {
            background-color: rgba(0, 0, 0, 0.7);
            min-height: 100vh;
            padding: 20px;
        }
        .navbar {
            background: rgba(52, 152, 219, 0.95);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-radius: 8px;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
            font-weight: 600;
        }
        .search-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .search-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .search-form {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            gap: 10px;
        }
        .search-form input {
            padding: 10px 15px;
            width: 300px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.9);
        }
        .search-form button {
            padding: 10px 20px;
            background: #3498db;
            border: none;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.3s ease;
        }
        .search-form button:hover {
            background: #2980b9;
        }
        .search-form button i {
            font-size: 1.1rem;
        }
        .results-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .course-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .course-card h3 {
            margin: 0 0 10px 0;
            color: #fff;
        }
        .category {
            color: #3498db;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .enrolled-badge {
            background: #27ae60;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-left: 10px;
        }
        .course-stats {
            display: flex;
            gap: 15px;
            margin: 10px 0;
            color: #bdc3c7;
            font-size: 0.9rem;
        }
        .price {
            color: #f1c40f;
            font-weight: bold;
            margin: 10px 0;
        }
        .description {
            color: #ecf0f1;
            margin: 10px 0;
            font-size: 0.9rem;
        }
        .requirements {
            color: #95a5a6;
            font-size: 0.9rem;
            margin: 10px 0;
        }
        .course-card button, .course-card a {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }
        .course-card button:hover, .course-card a:hover {
            background: #2980b9;
        }
        .course-card button.selected {
            background: #27ae60;
        }
        .no-results {
            text-align: center;
            color: #fff;
            font-size: 1.2rem;
            margin-top: 50px;
        }
        .error-message {
            background-color: #ff6b6b;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 20px auto;
            max-width: 600px;
            text-align: center;
        }
        .search-stats {
            text-align: center;
            color: #fff;
            margin: 20px 0;
            font-size: 0.9rem;
        }
        .course-stats {
            display: flex;
            gap: 15px;
            margin: 10px 0;
            font-size: 0.9rem;
            color: #666;
        }
        .course-stats span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .course-stats i {
            color: #3498db;
        }
        .debug-info {
            background: rgba(0, 0, 0, 0.8);
            color: #fff;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="overlay">
        <div class="navbar">
            <div style="display: flex; align-items: center;">
                <img src="image/learnzy-logo.png" alt="Learnzy Logo" style="height: 60px; width: auto;">
            </div>
            <div>
                <a href="student_dashboard.php">Back to Dashboard</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>

        <div class="search-container">
            <div class="search-header">
                <h2>Search Results for: "<?= htmlspecialchars($q) ?>"</h2>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                    <p style="margin-top: 10px; font-size: 0.9rem;">
                        Please try again or contact support if the problem persists.
                    </p>
                </div>
            <?php endif; ?>

            <form class="search-form" action="search.php" method="GET">
                <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" 
                       placeholder="Search courses..." 
                       autocomplete="off"
                       required>
                <button type="submit">
                    <i class="fas fa-search"></i>
                    Search
                </button>
            </form>

            <?php if (!empty($q)): ?>
                <div class="search-stats">
                    Found <?= count($searchResults) ?> result<?= count($searchResults) !== 1 ? 's' : '' ?> for "<?= htmlspecialchars($q) ?>"
                </div>
            <?php endif; ?>

            <div class="results-container">
                <?php if (empty($q)): ?>
                    <div class="no-results">
                        <p>Please enter a search term to find courses.</p>
                    </div>
                <?php elseif (empty($searchResults)): ?>
                    <div class="no-results">
                        <p>No courses found matching your search.</p>
                        <p style="margin-top: 10px; font-size: 0.9rem;">Try different keywords or check your spelling.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($searchResults as $course): ?>
                        <div class="course-card">
                            <h3><?= htmlspecialchars($course['title']) ?></h3>
                            <div class="category"><?= htmlspecialchars($course['category']) ?></div>
                            <?php if ($course['is_enrolled']): ?>
                                <span class="enrolled-badge">Enrolled</span>
                            <?php endif; ?>
                            <div class="course-stats">
                                <span><i class="fas fa-book"></i> <?= $course['material_count'] ?> Materials</span>
                                <span><i class="fas fa-question-circle"></i> <?= $course['quiz_count'] ?> Quizzes</span>
                            </div>
                            <div class="price">💲<?= number_format($course['price'], 2) ?></div>
                            <div class="description"><?= htmlspecialchars(substr($course['description'], 0, 150)) ?>...</div>
                            <?php if ($course['requirements']): ?>
                                <div class="requirements" style="margin: 10px 0; font-size: 0.9rem; color: #666;">
                                    <strong>Requirements:</strong> <?= htmlspecialchars(substr($course['requirements'], 0, 100)) ?>...
                                </div>
                            <?php endif; ?>
                            <?php if (!$course['is_enrolled']): ?>
                                <button onclick="markCourse(this, <?= $course['id'] ?>, '<?= htmlspecialchars($course['title']) ?>', <?= $course['price'] ?>)">
                                    Select Course
                                </button>
                            <?php else: ?>
                                <a href="view_course.php?course=<?= $course['id'] ?>">View Course</a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        let paidCourses = new Set();
        let selectedCourses = new Map();

        // Load previously paid courses
        window.addEventListener('DOMContentLoaded', () => {
            fetch('get_paid_courses.php')
                .then(res => res.json())
                .then(courses => {
                    courses.forEach(course => {
                        paidCourses.add(course.course_name);
                    });
                })
                .catch(error => {
                    console.error('Error loading paid courses:', error);
                });
        });

        function markCourse(button, courseId, courseName, price) {
            if (button.classList.contains("selected")) {
                // Unselect
                button.innerText = "Select Course";
                button.style.backgroundColor = "#3498db";
                button.classList.remove("selected");
                selectedCourses.delete(courseId);
            } else {
                if (paidCourses.has(courseName)) {
                    alert(`⚠️ You have already paid for "${courseName}".`);
                } else {
                    // Select
                    button.innerText = "✅ Selected";
                    button.style.backgroundColor = "#27ae60";
                    button.classList.add("selected");
                    selectedCourses.set(courseId, { name: courseName, price: price });
                }
            }
        }

        // Add search form validation
        document.querySelector('.search-form').addEventListener('submit', function(e) {
            const searchInput = this.querySelector('input[name="q"]');
            if (!searchInput.value.trim()) {
                e.preventDefault();
                alert('Please enter a search term');
            }
        });
    </script>
</body>
</html>
