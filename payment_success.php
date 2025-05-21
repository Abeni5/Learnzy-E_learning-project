<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.html");
    exit;
}

// Get payment details from URL parameters
$payment_id = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : 0;

// Fetch payment and enrollment details
$stmt = $conn->prepare("
    SELECT p.*, GROUP_CONCAT(ce.course_name) as courses
    FROM payments p
    JOIN course_enrollments ce ON p.payment_id = ce.payment_id
    WHERE p.payment_id = ? AND p.user_id = ?
    GROUP BY p.payment_id
");
$stmt->bind_param("ii", $payment_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$payment = $result->fetch_assoc();

if (!$payment) {
    header("Location: student_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Successful - Learnzy</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('image/background.png') no-repeat center center fixed;
            background-size: cover;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .success-container {
            background: rgba(0, 0, 0, 0.8);
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            max-width: 600px;
            width: 90%;
        }
        .success-icon {
            font-size: 64px;
            color: #2ecc71;
            margin-bottom: 20px;
        }
        h1 {
            color: #2ecc71;
            margin-bottom: 20px;
        }
        .details {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }
        .details p {
            margin: 10px 0;
            color: #ddd;
        }
        .details strong {
            color: #fff;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
            margin: 10px;
        }
        .button:hover {
            background-color: #2980b9;
        }
        .courses-list {
            margin: 20px 0;
            padding: 0;
            list-style: none;
        }
        .courses-list li {
            background: rgba(255, 255, 255, 0.1);
            margin: 5px 0;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">✅</div>
        <h1>Enrollment Successful!</h1>
        
        <div class="details">
            <p><strong>Payment ID:</strong> #<?= $payment['payment_id'] ?></p>
            <p><strong>Amount Paid:</strong> 💲<?= number_format($payment['amount'], 2) ?></p>
            <p><strong>Payment Method:</strong> <?= ucfirst($payment['payment_method']) ?></p>
            <p><strong>Date:</strong> <?= date('F j, Y', strtotime($payment['payment_date'])) ?></p>
            
            <h3 style="color: #2ecc71; margin-top: 20px;">Enrolled Courses:</h3>
            <ul class="courses-list">
                <?php foreach (explode(',', $payment['courses']) as $course): ?>
                    <li><?= htmlspecialchars($course) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <p style="margin: 20px 0; color: #2ecc71;">
            Your enrollment has been confirmed. You can now access your courses from your dashboard.
        </p>

        <div>
            <a href="student_dashboard.php" class="button">Return to Dashboard</a>
            <a href="my_courses.php" class="button">View My Courses</a>
        </div>
    </div>
</body>
</html> 