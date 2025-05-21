<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "elearning_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all tables
$result = $conn->query("SHOW TABLES");
echo "<h2>Tables in elearning_db:</h2>";
while ($row = $result->fetch_array()) {
    echo "<br>Table: " . $row[0];
    
    // Get table structure
    $columns = $conn->query("SHOW COLUMNS FROM " . $row[0]);
    echo "<ul>";
    while ($col = $columns->fetch_assoc()) {
        echo "<li>" . $col['Field'] . " - " . $col['Type'] . "</li>";
    }
    echo "</ul>";
}

$conn->close();
?> 