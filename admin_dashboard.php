<?php
session_start();

// Redirect non-admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

// Connect to database
$conn = new mysqli("localhost", "root", "", "elearning_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = !empty($_POST['title']) ? $_POST['title'] : '';
    $category = !empty($_POST['category_select']) ? $_POST['category_select'] : $_POST['category'];
    $description = $_POST['description'] ?? '';
    $requirements = $_POST['requirements'] ?? '';
    $price = $_POST['price'] ?? 0;
    $course_id = !empty($_POST['course_id']) ? $_POST['course_id'] : null;

    // Handle file uploads
    $pdf_path = '';
    $quiz_path = '';

    if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
        $pdf_dir = 'lectures/';
        if (!file_exists($pdf_dir)) {
            mkdir($pdf_dir, 0777, true);
        }
        $pdf_name = time() . '_' . basename($_FILES['pdf']['name']);
        $pdf_path = $pdf_dir . $pdf_name;
        move_uploaded_file($_FILES['pdf']['tmp_name'], $pdf_path);
    }

    if (isset($_FILES['quiz']) && $_FILES['quiz']['error'] === UPLOAD_ERR_OK) {
        $quiz_dir = 'quizzes/';
        if (!file_exists($quiz_dir)) {
            mkdir($quiz_dir, 0777, true);
        }
        $quiz_name = time() . '_' . basename($_FILES['quiz']['name']);
        $quiz_path = $quiz_dir . $quiz_name;
        move_uploaded_file($_FILES['quiz']['tmp_name'], $quiz_path);
    }

    if ($course_id) {
        // Update existing course
        $updates = [];
        $types = '';
        $params = [];
        $changed_fields = [];

        $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_course = $result->fetch_assoc();
        $stmt->close();

        if (!$current_course) {
            $message = "❌ Course not found!";
        } else {
            // Only update fields that are provided
            if (!empty($title) && $title !== $current_course['title']) {
                $updates[] = "title = ?";
                $types .= "s";
                $params[] = $title;
                $changed_fields[] = "title";
            }
            if (!empty($category) && $category !== $current_course['category']) {
                $updates[] = "category = ?";
                $types .= "s";
                $params[] = $category;
                $changed_fields[] = "category";
            }
            if (!empty($price) && $price != $current_course['price']) {
                $updates[] = "price = ?";
                $types .= "i";
                $params[] = $price;
                $changed_fields[] = "price";
            }
            if (!empty($description) && $description !== $current_course['description']) {
                $updates[] = "description = ?";
                $types .= "s";
                $params[] = $description;
                $changed_fields[] = "description";
            }
            if (!empty($requirements) && $requirements !== $current_course['requirements']) {
                $updates[] = "requirements = ?";
                $types .= "s";
                $params[] = $requirements;
                $changed_fields[] = "requirements";
            }
            if (!empty($pdf_path)) {
                $updates[] = "pdf_path = ?";
                $types .= "s";
                $params[] = $pdf_path;
                $changed_fields[] = "PDF lecture";
            }
            if (!empty($quiz_path)) {
                $updates[] = "quiz_path = ?";
                $types .= "s";
                $params[] = $quiz_path;
                $changed_fields[] = "quiz";
            }

            if (!empty($updates)) {
                $query = "UPDATE courses SET " . implode(", ", $updates) . " WHERE id = ?";
                $types .= "i";
                $params[] = $course_id;

                $stmt = $conn->prepare($query);
                $stmt->bind_param($types, ...$params);

                if ($stmt->execute()) {
                    $message = "✅ Course updated successfully! Changed fields: " . implode(", ", $changed_fields);
                } else {
                    $message = "❌ Failed to update course: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $message = "ℹ️ No changes were made to the course.";
            }
        }
    } else {
        // Add new course
        if (empty($title) && empty($category) && empty($price) && empty($description) && empty($requirements)) {
            $message = "❌ Please fill in at least one field to add a new course.";
        } else {
            $stmt = $conn->prepare("INSERT INTO courses (title, category, description, requirements, pdf_path, quiz_path, price) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssi", $title, $category, $description, $requirements, $pdf_path, $quiz_path, $price);

            if ($stmt->execute()) {
                $message = "✅ New course added successfully!";
            } else {
                $message = "❌ Failed to add course: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch existing categories from database
$categories = $conn->query("SELECT DISTINCT category FROM courses ORDER BY category");

// Fetch all courses for the dropdown
$courses = $conn->query("SELECT * FROM courses ORDER BY category, title");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin - Course Management</title>
  <style>
    body { font-family: Arial; margin: 0; display: flex; }
    .sidebar {
      width: 200px; background: #2c3e50; color: white; height: 100vh; padding: 20px;
    }
    .sidebar a {
      color: white; display: block; margin: 10px 0; text-decoration: none;
    }
    .content {
      flex: 1; padding: 30px;
    }
    form {
      background: #f5f5f5; padding: 20px; max-width: 600px; border-radius: 8px;
    }
    input, textarea, select, button {
      width: 100%; margin: 10px 0; padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
    }
    select {
      background-color: white;
      cursor: pointer;
    }
    button {
      background: #2ecc71; color: white; border: none; cursor: pointer;
      font-weight: bold;
    }
    button:hover {
      background: #27ae60;
    }
    .message {
      color: green;
      padding: 10px;
      margin: 10px 0;
      border-radius: 4px;
      background: #e8f5e9;
    }
    .form-group {
      margin-bottom: 15px;
    }
    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
      color: #333;
    }
    .new-input {
      margin-top: 10px;
    }
    .price-input {
      width: 100px !important;
      display: inline-block;
      margin-left: 10px;
    }
    .price-label {
      display: inline-block;
      margin-left: 10px;
    }
    .courses-list {
      margin-top: 40px;
      background: #f5f5f5;
      padding: 20px;
      border-radius: 8px;
    }
    .course-item {
      background: white;
      padding: 20px;
      margin: 15px 0;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      transition: transform 0.2s ease;
    }
    .course-item:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    .course-info {
      flex: 1;
    }
    .course-title {
      font-size: 1.2em;
      color: #2c3e50;
      margin-bottom: 8px;
    }
    .course-category {
      color: #7f8c8d;
      font-size: 0.9em;
      margin-bottom: 5px;
    }
    .course-price {
      color: #27ae60;
      font-weight: bold;
      font-size: 1.1em;
    }
    .delete-btn {
      background: #e74c3c;
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 4px;
      cursor: pointer;
      margin-left: 15px;
    }
    .delete-btn:hover {
      background: #c0392b;
    }
    .no-courses {
      text-align: center;
      color: #7f8c8d;
      padding: 20px;
    }
    #previewSection {
      margin-top: 20px;
      padding: 15px;
      background: #fff;
      border-radius: 4px;
      border: 1px solid #ddd;
    }
    #previewSection a {
      color: #2ecc71;
      text-decoration: none;
    }
    #previewSection a:hover {
      text-decoration: underline;
    }
    .form-select {
      font-size: 1.1em;
      padding: 12px;
      border: 2px solid #ddd;
      border-radius: 6px;
      transition: border-color 0.3s ease;
    }
    
    .form-select:focus {
      border-color: #3498db;
      outline: none;
    }
    
    .form-group label {
      font-size: 1.1em;
      color: #2c3e50;
      margin-bottom: 10px;
    }
    .new-input-group {
      margin-top: 10px;
      padding: 10px;
      background: #f8f9fa;
      border-radius: 4px;
    }
    .new-input-group label {
      display: block;
      margin-bottom: 5px;
      color: #666;
    }
    .new-input {
      width: 100%;
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 4px;
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
    <h1>Course Management</h1>

    <?php if (!empty($message)): ?>
      <p class="message"><?= $message ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <label for="category_select">Course Category:</label>
        <select name="category_select" id="category_select" onchange="handleCategorySelect(this)" class="form-select">
          <option value="">-- Select Category --</option>
          <?php 
          $categories->data_seek(0);
          while ($row = $categories->fetch_assoc()): 
          ?>
            <option value="<?= htmlspecialchars($row['category']) ?>"><?= htmlspecialchars($row['category']) ?></option>
          <?php endwhile; ?>
        </select>
        <div class="new-input-group">
          <label for="category">Or Enter New Category:</label>
          <input type="text" name="category" id="category" class="new-input" placeholder="Enter new category">
        </div>
      </div>

      <div class="form-group">
        <label for="title">Course Title:</label>
        <input type="text" name="title" id="title" class="new-input" placeholder="Enter course title" required>
      </div>

      <div class="form-group">
        <label for="price">Course Price ($):</label>
        <input type="number" name="price" id="price" min="0" step="0.01" required>
      </div>

      <div class="form-group">
        <label for="description">Course Description:</label>
        <textarea name="description" id="description" rows="4" required></textarea>
      </div>

      <div class="form-group">
        <label for="requirements">Course Requirements:</label>
        <textarea name="requirements" id="requirements" rows="4" required></textarea>
      </div>

      <div class="form-group">
        <label for="pdf">Upload PDF Lecture:</label>
        <input type="file" name="pdf" id="pdf" accept="application/pdf" required>
      </div>

      <div class="form-group">
        <label for="quiz">Upload Quiz:</label>
        <input type="file" name="quiz" id="quiz" accept=".json" required>
      </div>

      <button type="submit" id="submit_btn">Add New Course</button>
    </form>

    <script>
      function handleCategorySelect(select) {
        const categoryInput = document.getElementById('category');
        if (select.value) {
          categoryInput.value = select.value;
          categoryInput.disabled = true;
        } else {
          categoryInput.disabled = false;
        }
      }

      // Initialize the form state
      document.addEventListener('DOMContentLoaded', function() {
        handleCategorySelect(document.getElementById('category_select'));
      });
    </script>
  </div>
</body>
</html>
