<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

include 'db_connection.php';

// Fetch summary stats
$totalCourses = $conn->query("SELECT COUNT(*) AS count FROM courses")->fetch_assoc()['count'];
$totalStudents = $conn->query("SELECT COUNT(*) AS count FROM users WHERE role = 'student'")->fetch_assoc()['count'];
$totalEnrollments = $conn->query("SELECT COUNT(*) AS count FROM course_enrollments")->fetch_assoc()['count'];
$totalQuizzes = $conn->query("SELECT COUNT(*) AS count FROM course_quizzes")->fetch_assoc()['count'];

// Initialize enrollments array
$enrollments = [];

// Apply date filter if specified
$whereClause = "";
if (isset($_GET['range']) && is_numeric($_GET['range'])) {
    $days = intval($_GET['range']);
    $whereClause = "WHERE ce.enrollment_date >= DATE_SUB(CURDATE(), INTERVAL $days DAY)";
}

// Fetch course enrollments with course details and quiz statistics
$query = "
    SELECT 
        c.title as course_name,
        c.category,
        COUNT(DISTINCT ce.student_id) as student_count,
        MIN(ce.enrollment_date) as first_enrollment,
        MAX(ce.enrollment_date) as last_enrollment,
        COALESCE((SELECT COUNT(*) FROM course_quizzes WHERE course_id = c.id), 0) as total_quizzes,
        COALESCE((SELECT COUNT(DISTINCT sqa.student_id) 
         FROM student_quiz_attempts sqa 
         JOIN course_quizzes cq ON sqa.quiz_id = cq.id 
         WHERE cq.course_id = c.id), 0) as students_taken_quiz,
        COALESCE((SELECT AVG(sqa.score) 
         FROM student_quiz_attempts sqa 
         JOIN course_quizzes cq ON sqa.quiz_id = cq.id 
         WHERE cq.course_id = c.id), 0) as average_score
    FROM courses c
    LEFT JOIN course_enrollments ce ON c.id = ce.course_id
    $whereClause
    GROUP BY c.id, c.title, c.category
    ORDER BY student_count DESC, c.title
";

$result = $conn->query($query);
if ($result) {
    $enrollments = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Reports | Course Enrollments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 40px;
            color: #333;
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        .stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-bottom: 40px;
        }
        .stat-box {
            background: #3498db;
            color: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            width: 200px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.2);
            transition: transform 0.2s;
        }
        .stat-box:hover {
            transform: translateY(-5px);
        }
        .stat-box i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .chart-container {
            display: flex;
            gap: 20px;
            margin: 30px 0;
        }
        .chart-box {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            margin: 20px auto;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #2c3e50;
            color: white;
        }
        tr:hover {
            background-color: #f9f9f9;
        }
        .back {
            display: block;
            margin: 30px auto;
            width: fit-content;
            padding: 10px 25px;
            background: #2c3e50;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .back:hover {
            background: #1a252f;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filter-form {
            text-align: center;
            margin-bottom: 20px;
        }
        .filter-form select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
            margin-right: 10px;
        }
        .filter-form button {
            padding: 8px 15px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .student-count {
            font-weight: bold;
            color: #3498db;
        }
        .quiz-stats {
            display: flex;
            gap: 10px;
            align-items: center;
            color: #666;
            font-size: 0.9rem;
        }
        .quiz-stats i {
            color: #9b59b6;
        }
        .score-badge {
            background: #f1c40f;
            color: #333;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .progress-bar {
            width: 100%;
            height: 6px;
            background: #eee;
            border-radius: 3px;
            margin-top: 5px;
        }
        .progress-fill {
            height: 100%;
            background: #3498db;
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .badge-success {
            background-color: #27ae60;
            color: white;
        }
        .badge-info {
            background-color: #3498db;
            color: white;
        }
    </style>
</head>
<body>
    <h1><i class="fas fa-chart-bar"></i> Course Enrollment Reports</h1>

    <div class="stats">
        <div class="stat-box">
            <i class="fas fa-book-open"></i>
            <h2><?= $totalCourses ?></h2>
            <p>Total Courses</p>
        </div>
        <div class="stat-box" style="background: #27ae60;">
            <i class="fas fa-users"></i>
            <h2><?= $totalStudents ?></h2>
            <p>Registered Students</p>
        </div>
        <div class="stat-box" style="background: #e67e22;">
            <i class="fas fa-user-graduate"></i>
            <h2><?= $totalEnrollments ?></h2>
            <p>Total Enrollments</p>
        </div>
        <div class="stat-box">
            <i class="fas fa-question-circle"></i>
            <h2><?= $totalQuizzes ?></h2>
            <p>Total Quizzes</p>
        </div>
    </div>

    <div class="filter-form">
        <form method="GET">
            <select name="range">
                <option value="">All Time</option>
                <option value="7" <?= isset($_GET['range']) && $_GET['range'] == '7' ? 'selected' : '' ?>>Last 7 Days</option>
                <option value="30" <?= isset($_GET['range']) && $_GET['range'] == '30' ? 'selected' : '' ?>>Last 30 Days</option>
                <option value="90" <?= isset($_GET['range']) && $_GET['range'] == '90' ? 'selected' : '' ?>>Last 90 Days</option>
            </select>
            <button type="submit">Apply Filter</button>
        </form>
    </div>

    <?php if (!empty($enrollments)): ?>
        <div class="chart-container">
            <div class="chart-box">
                <canvas id="enrollmentChart"></canvas>
            </div>
            <div class="chart-box">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>📘 Course Name</th>
                    <th>📁 Category</th>
                    <th>👥 Enrolled Students</th>
                    <th>📝 Quiz Statistics</th>
                    <th>📊 Average Score</th>
                    <th>📅 First Enrollment</th>
                    <th>📅 Last Enrollment</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($enrollments as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['course_name']) ?></td>
                        <td><?= htmlspecialchars($row['category'] ?? 'Uncategorized') ?></td>
                        <td class="student-count"><?= $row['student_count'] ?></td>
                        <td>
                            <div class="quiz-stats">
                                <i class="fas fa-question-circle"></i>
                                <?= $row['students_taken_quiz'] ?> / <?= $row['student_count'] ?> students
                                <?php if ($row['total_quizzes'] > 0): ?>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?= ($row['student_count'] > 0 ? ($row['students_taken_quiz'] / $row['student_count'] * 100) : 0) ?>%"></div>
                                    </div>
                                <?php else: ?>
                                    <span style="color: #999; font-size: 0.8rem;">No quizzes available</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($row['average_score'] > 0): ?>
                                <span class="score-badge">
                                    <?= number_format($row['average_score'], 1) ?>%
                                </span>
                            <?php else: ?>
                                <span style="color: #999;">No scores yet</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $row['student_count'] > 0 ? date('M d, Y', strtotime($row['first_enrollment'])) : '-' ?></td>
                        <td><?= $row['student_count'] > 0 ? date('M d, Y', strtotime($row['last_enrollment'])) : '-' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-data">
            <p>📊 No enrollment data available yet.</p>
            <p>Students will appear here once they enroll in courses.</p>
        </div>
    <?php endif; ?>

    <a class="back" href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

    <?php if (!empty($enrollments)): ?>
    <script>
        // Prepare data for charts
        const courseNames = <?= json_encode(array_column($enrollments, 'course_name')) ?>;
        const studentCounts = <?= json_encode(array_column($enrollments, 'student_count')) ?>;
        const categories = <?= json_encode(array_column($enrollments, 'category')) ?>;

        // Enrollment by Course Chart
        const enrollmentCtx = document.getElementById('enrollmentChart').getContext('2d');
        new Chart(enrollmentCtx, {
            type: 'bar',
            data: {
                labels: courseNames,
                datasets: [{
                    label: 'Enrolled Students',
                    data: studentCounts,
                    backgroundColor: 'rgba(52, 152, 219, 0.8)',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Enrollments by Course'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Category Distribution Chart
        const categoryData = categories.reduce((acc, cat) => {
            cat = cat || 'Uncategorized';
            acc[cat] = (acc[cat] || 0) + 1;
            return acc;
        }, {});

        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(categoryData),
                datasets: [{
                    data: Object.values(categoryData),
                    backgroundColor: [
                        'rgba(52, 152, 219, 0.8)',
                        'rgba(46, 204, 113, 0.8)',
                        'rgba(155, 89, 182, 0.8)',
                        'rgba(230, 126, 34, 0.8)',
                        'rgba(241, 196, 15, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Courses by Category'
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
