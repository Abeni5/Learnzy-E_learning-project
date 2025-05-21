<?php
// reset_password.php

// Check if email is passed
if (!isset($_GET['email'])) {
    echo "❌ Invalid access.";
    exit;
}

$email = $_GET['email'];
?>
<!DOCTYPE html>
<html>
<head>
  <title>Reset Password</title>
</head>
<body>
  <h2>Reset Your Password</h2>
  <form action="reset_password_save.php" method="POST">
    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
    <input type="password" name="new_password" placeholder="New Password" required><br>
    <input type="password" name="confirm_password" placeholder="Confirm Password" required><br>
    <button type="submit">Reset Password</button>
  </form>
</body>
</html>
