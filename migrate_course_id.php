<?php
include 'db_connection.php';

try {
    $conn->begin_transaction();

    // Check if course_id column exists
    $result = $conn->query("SHOW COLUMNS FROM course_enrollments LIKE 'course_id'");
    if ($result->num_rows == 0) {
        // Add course_id column if it doesn't exist
        $conn->query("ALTER TABLE course_enrollments ADD COLUMN course_id INT AFTER student_id");
    }

    // Update course_id values based on course_name
    $conn->query("
        UPDATE course_enrollments ce
        JOIN courses c ON ce.course_name = c.title
        SET ce.course_id = c.id
        WHERE ce.course_id IS NULL
    ");

    // Make course_id NOT NULL after data migration
    $conn->query("ALTER TABLE course_enrollments MODIFY course_id INT NOT NULL");

    // Add foreign key constraint
    $conn->query("ALTER TABLE course_enrollments ADD CONSTRAINT fk_course_id FOREIGN KEY (course_id) REFERENCES courses(id)");

    // Drop the unique constraint on student_id and course_name
    $conn->query("ALTER TABLE course_enrollments DROP INDEX student_id");

    // Add new unique constraint on student_id and course_id
    $conn->query("ALTER TABLE course_enrollments ADD UNIQUE INDEX student_course_unique (student_id, course_id)");

    // Drop the course_name column
    $conn->query("ALTER TABLE course_enrollments DROP COLUMN course_name");

    $conn->commit();
    echo "Migration completed successfully!\n";
} catch (Exception $e) {
    $conn->rollback();
    echo "Error during migration: " . $e->getMessage() . "\n";
}

$conn->close();
?> 