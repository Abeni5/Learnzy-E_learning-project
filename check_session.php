<?php
session_start();
echo "<pre>";
echo "Session Status:\n";
echo "user_id: " . ($_SESSION['user_id'] ?? 'not set') . "\n";
echo "session_id: " . session_id() . "\n";
echo "session_name: " . session_name() . "\n";
echo "</pre>";
?> 