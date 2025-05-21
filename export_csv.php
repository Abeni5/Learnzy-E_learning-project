<?php
include 'db_connection.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="course_report.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Course Name', 'Enrolled Students']);

$query = "
    SELECT c.title as course_name, COUNT(*) AS student_count 
    FROM course_enrollments ce
    JOIN courses c ON ce.course_id = c.id
    GROUP BY c.title
";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [$row['course_name'], $row['student_count']]);
}

fclose($output);
exit;
?>
