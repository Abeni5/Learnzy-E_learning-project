<?php
// forgot_password.php
$conn = new mysqli("localhost", "root", "", "elearning_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    header("Location: reset_password.php?email=" . urlencode($email));
    exit;
    // Check if email exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // In a real app, you'd send email or show a reset page.
        echo "✅ A password reset link would be sent to: $email";
    } else {
        echo "❌ No user found with that email.";
    }
}
?>
