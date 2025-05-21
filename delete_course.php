<?php
require_once 'config.php';
requireAdmin();

$message = '';

// Handle course deletion
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = getDBConnection();
    
    if (isset($_POST['course_id']) && isset($_POST['action'])) {
        $course_id = $_POST['course_id'];
        
        if ($_POST['action'] === 'delete') {
            // Get file paths before deleting
            $stmt = $conn->prepare("SELECT pdf_path, quiz_path FROM courses WHERE id = ?");
            $stmt->bind_param("i", $course_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $course = $result->fetch_assoc();
            
            // Delete files if they exist
            if (!empty($course['pdf_path']) && file_exists($course['pdf_path'])) {
                unlink($course['pdf_path']);
            }
            if (!empty($course['quiz_path']) && file_exists($course['quiz_path'])) {
                unlink($course['quiz_path']);
            }
            
            // Delete course from database
            $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->bind_param("i", $course_id);
            
            if ($stmt->execute()) {
                $message = "✅ Course deleted successfully!";
            } else {
                $message = "❌ Failed to delete course: " . $stmt->error;
            }
        } elseif ($_POST['action'] === 'update') {
            $title = $_POST['title'];
            $description = $_POST['description'];
            $category = $_POST['category'];
            $requirements = $_POST['requirements'];
            $price = $_POST['price'];
            
            $stmt = $conn->prepare("UPDATE courses SET title = ?, description = ?, category = ?, requirements = ?, price = ? WHERE id = ?");
            $stmt->bind_param("ssssdi", $title, $description, $category, $requirements, $price, $course_id);
            
            if ($stmt->execute()) {
                $message = "✅ Course updated successfully!";
            } else {
                $message = "❌ Failed to update course: " . $stmt->error;
            }
        }
        $stmt->close();
    }
    $conn->close();
}

// Get database connection
$conn = getDBConnection();

// Fetch all courses for display
$courses = $conn->query("SELECT * FROM courses ORDER BY title, category");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Courses</title>
    <style>
        body { 
            margin: 0; 
            font-family: Arial, sans-serif; 
            display: flex; 
            background: #f5f5f5;
        }
        .sidebar {
            width: 200px; 
            background: #2c3e50; 
            color: white; 
            height: 100vh; 
            padding: 20px;
        }
        .sidebar a {
            color: white; 
            display: block; 
            margin: 10px 0; 
            text-decoration: none;
        }
        .content {
            flex: 1; 
            padding: 30px;
        }
        .message { 
            color: green; 
            margin-bottom: 20px;
            padding: 10px;
            background: #e8f5e9;
            border-radius: 4px;
        }
        .course-list {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .course-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .course-item:last-child {
            border-bottom: none;
        }
        .course-info {
            flex: 1;
        }
        .course-title {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .course-category {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        .btn {
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }
        .edit-btn {
            background: #3498db;
        }
        .edit-btn:hover {
            background: #2980b9;
        }
        .delete-btn {
            background: #e74c3c;
        }
        .delete-btn:hover {
            background: #c0392b;
        }
        .no-courses {
            text-align: center;
            color: #7f8c8d;
            padding: 20px;
        }
        .edit-form {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .edit-form input[type="text"],
        .edit-form input[type="number"],
        .edit-form textarea {
            width: 100%;
            padding: 8px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .edit-form textarea {
            height: 100px;
        }
        .edit-form .form-buttons {
            margin-top: 10px;
        }
        .save-btn {
            background: #2ecc71;
        }
        .save-btn:hover {
            background: #27ae60;
        }
        .cancel-btn {
            background: #95a5a6;
        }
        .cancel-btn:hover {
            background: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Admin</h2>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="admin_dashboard.php">Add Course</a>
        <a href="delete_course.php">Update/Delete Course</a>
        <a href="reports.php">Reports</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="content">
        <h1>Manage Courses</h1>
        
        <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>

        <div class="course-list">
            <h2>Existing Courses</h2>
            <?php if ($courses->num_rows > 0): ?>
                <?php while ($course = $courses->fetch_assoc()): ?>
                    <div class="course-item">
                        <div class="course-info">
                            <div class="course-title"><?= htmlspecialchars($course['title']) ?></div>
                            <div class="course-category"><?= htmlspecialchars($course['category']) ?></div>
                        </div>
                        <div>
                            <button class="btn edit-btn" onclick="toggleEditForm(<?= $course['id'] ?>)">Edit</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this course?');">
                                <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn delete-btn">Delete</button>
                            </form>
                        </div>
                    </div>
                    <div id="edit-form-<?= $course['id'] ?>" class="edit-form">
                        <form method="POST">
                            <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                            <input type="hidden" name="action" value="update">
                            <div>
                                <label>Title:</label>
                                <input type="text" name="title" value="<?= htmlspecialchars($course['title']) ?>" required>
                            </div>
                            <div>
                                <label>Category:</label>
                                <input type="text" name="category" value="<?= htmlspecialchars($course['category']) ?>" required>
                            </div>
                            <div>
                                <label>Description:</label>
                                <textarea name="description" required><?= htmlspecialchars($course['description']) ?></textarea>
                            </div>
                            <div>
                                <label>Requirements:</label>
                                <textarea name="requirements" required><?= htmlspecialchars($course['requirements']) ?></textarea>
                            </div>
                            <div>
                                <label>Price ($):</label>
                                <input type="number" name="price" value="<?= htmlspecialchars($course['price']) ?>" step="0.01" min="0" required>
                            </div>
                            <div class="form-buttons">
                                <button type="submit" class="btn save-btn">Save Changes</button>
                                <button type="button" class="btn cancel-btn" onclick="toggleEditForm(<?= $course['id'] ?>)">Cancel</button>
                            </div>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-courses">No courses found.</div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleEditForm(courseId) {
            const form = document.getElementById(`edit-form-${courseId}`);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>
<?php $conn->close(); ?> 