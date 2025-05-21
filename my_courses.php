<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.html");
    exit;
}

include 'db_connection.php';
$student_id = $_SESSION['user_id'];

// Get course list from course_enrollments table
$query = $conn->prepare("
  SELECT c.title, c.description, c.category
  FROM course_enrollments ce
  JOIN courses c ON ce.course_id = c.id
  WHERE ce.student_id = ?
");
$query->bind_param("i", $student_id);
$query->execute();
$result = $query->get_result();
$courses = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Courses | Learnzy</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f0f4f8;
      margin: 0;
      padding: 30px;
    }
    h1 {
      text-align: center;
      margin-bottom: 30px;
      color: #2c3e50;
    }
    .course-box {
      background: white;
      padding: 20px;
      margin: 20px auto;
      max-width: 700px;
      border-radius: 10px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    .course-box h2 {
      margin-bottom: 10px;
      color: #3498db;
    }
    .course-box p {
      color: #555;
    }
    .badge {
      display: inline-block;
      padding: 4px 10px;
      font-size: 0.85rem;
      background: #2ecc71;
      color: white;
      border-radius: 6px;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>

<h1><i class="fas fa-graduation-cap"></i> My Courses</h1>

<?php if (count($courses) > 0): ?>
  <?php foreach ($courses as $course): ?>
    <div class="course-box">
      <span class="badge"><?= htmlspecialchars($course['category']) ?></span>
      <h2><?= htmlspecialchars($course['title']) ?></h2>
      <p><?= htmlspecialchars($course['description']) ?></p>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <p style="text-align: center;">📭 You haven't enrolled in any courses yet.</p>
<?php endif; ?>

</body>
</html>
