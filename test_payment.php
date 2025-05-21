<?php
session_start();
include 'db_connection.php';

// Test user data
$_SESSION['user_id'] = 1; // Replace with an actual user ID from your database
$_SESSION['role'] = 'student';
$_SESSION['username'] = 'test_user'; // Add username for authentication

// Test payment data
$testData = [
    'courses' => [
        [
            'course_name' => 'Programming'
        ],
        [
            'course_name' => 'Web Development'
        ]
    ]
];

// Convert to JSON
$jsonData = json_encode($testData);

// Set up cURL request
$ch = curl_init('http://localhost/project/enroll_course.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id()); // Add session cookie
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonData)
]);

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Display results
echo "<h2>Payment System Test Results</h2>";
echo "<pre>";
echo "HTTP Status Code: " . $httpCode . "\n\n";
echo "Response:\n";
print_r(json_decode($response, true));
echo "</pre>";

// Check database
echo "<h3>Database Check:</h3>";
echo "<pre>";

try {
    // Check if tables exist
    $tables = $conn->query("SHOW TABLES LIKE 'payments'");
    if ($tables->num_rows === 0) {
        echo "Error: payments table does not exist. Please run create_tables.sql first.\n";
    } else {
        // Check payments table
        $result = $conn->query("SELECT * FROM payments ORDER BY payment_id DESC LIMIT 1");
        if ($result) {
            echo "Latest Payment:\n";
            print_r($result->fetch_assoc());
        } else {
            echo "Error checking payments: " . $conn->error . "\n";
        }
    }

    echo "\n";

    // Check enrollments table
    $tables = $conn->query("SHOW TABLES LIKE 'course_enrollments'");
    if ($tables->num_rows === 0) {
        echo "Error: course_enrollments table does not exist. Please run create_tables.sql first.\n";
    } else {
        $result = $conn->query("SELECT * FROM course_enrollments ORDER BY enrollment_id DESC LIMIT 2");
        if ($result) {
            echo "Latest Enrollments:\n";
            while ($row = $result->fetch_assoc()) {
                print_r($row);
            }
        } else {
            echo "Error checking enrollments: " . $conn->error . "\n";
        }
    }
} catch (Exception $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?> 