<?php
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if ($password !== $confirm) {
        echo "<script>alert('❌ Passwords do not match!'); window.history.back();</script>";
        exit;
    }

    // ✅ Fix: initialize $check first
    $check = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $check->bind_param("ss", $email, $username);
    $check->execute();

    $check->store_result(); // ✅ necessary for num_rows
    if ($check->num_rows > 0) {
        echo "<script>alert('❌ Email or username already exists!'); window.history.back();</script>";
        $check->close();
        $conn->close();
        exit;
    }

    $check->close();

    // ✅ Hash the password
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $role = 'student';

    // ✅ Insert new user
    $stmt = $conn->prepare("INSERT INTO users (name, email, username, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $username, $hashed, $role);

    if ($stmt->execute()) {
        echo "<script>alert('✅ Registration successful!'); window.location.href='login.html';</script>";
    } else {
        echo "<script>alert('❌ Registration failed. Try again.'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
