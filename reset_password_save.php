<?php
$conn = new mysqli("localhost", "root", "", "elearning_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass !== $confirm_pass) {
        echo "❌ Passwords do not match.";
        exit;
    }

    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashed, $email);

    if ($stmt->execute()) {
        echo "✅ Password updated. <a href='login.html'>Login</a>";
    } else {
        echo "❌ Error updating password: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
