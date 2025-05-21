<?php
session_start();
include 'db_connection.php';

$usernameOrEmail = $_POST['username'];
$password = $_POST['password'];
$inputRole = $_POST['role']; // from hidden field

// Fetch user with matching role
$stmt = $conn->prepare("SELECT * FROM users WHERE (email = ? OR username = ?) AND role = ?");
$stmt->bind_param("sss", $usernameOrEmail, $usernameOrEmail, $inputRole);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        ; 

        // Redirect by role
        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: student_dashboard.php");
        }
        exit;
    } else {
        echo "<script>alert('❌ Incorrect password'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('❌ No $inputRole account found with that email/username'); window.history.back();</script>";
}
?>
