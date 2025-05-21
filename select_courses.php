<?php
session_start();
include 'db_connection.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

// Get available courses
$stmt = $conn->prepare("SELECT * FROM courses WHERE status = 'active'");
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Courses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .course-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .selected {
            border: 2px solid #28a745;
            background-color: #f8fff8;
        }
        .confirmation-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .confirmation-content {
            position: relative;
            background-color: #fff;
            margin: 15% auto;
            padding: 20px;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h2 class="mb-4">Select Courses</h2>
        
        <form id="enrollmentForm">
            <div class="row">
                <?php foreach ($courses as $course): ?>
                <div class="col-md-4">
                    <div class="course-card" data-course-name="<?php echo htmlspecialchars($course['course_name']); ?>" 
                         data-price="<?php echo $course['price']; ?>">
                        <h4><?php echo htmlspecialchars($course['course_name']); ?></h4>
                        <p class="text-muted"><?php echo htmlspecialchars($course['department']); ?></p>
                        <p class="fw-bold">$<?php echo number_format($course['price'], 2); ?></p>
                        <div class="form-check">
                            <input class="form-check-input course-checkbox" type="checkbox" 
                                   name="courses[]" value="<?php echo htmlspecialchars($course['course_name']); ?>">
                            <label class="form-check-label">Select Course</label>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="row mt-4">
                <div class="col-md-6 offset-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h4>Order Summary</h4>
                            <div id="selectedCourses"></div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <h5>Total:</h5>
                                <h5 id="totalAmount">$0.00</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-center">
                <button type="submit" class="btn btn-primary btn-lg" id="payButton">
                    <span class="button-text">Pay & Enroll Now</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </form>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="confirmation-modal">
        <div class="confirmation-content">
            <h3>Confirm Enrollment</h3>
            <div id="confirmationDetails"></div>
            <div class="mt-4 text-center">
                <button type="button" class="btn btn-secondary me-2" onclick="closeConfirmation()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmEnrollment()">Confirm & Pay</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Handle course selection
            $('.course-checkbox').change(function() {
                const card = $(this).closest('.course-card');
                if (this.checked) {
                    card.addClass('selected');
                } else {
                    card.removeClass('selected');
                }
                updateSummary();
            });

            // Update order summary
            function updateSummary() {
                let total = 0;
                let selectedCourses = [];
                
                $('.course-checkbox:checked').each(function() {
                    const card = $(this).closest('.course-card');
                    const courseName = card.data('course-name');
                    const price = parseFloat(card.data('price'));
                    total += price;
                    selectedCourses.push({
                        course_name: courseName,
                        price: price
                    });
                });

                // Update summary display
                $('#selectedCourses').empty();
                selectedCourses.forEach(course => {
                    $('#selectedCourses').append(`
                        <div class="d-flex justify-content-between mb-2">
                            <span>${course.course_name}</span>
                            <span>$${course.price.toFixed(2)}</span>
                        </div>
                    `);
                });
                $('#totalAmount').text('$' + total.toFixed(2));

                // Enable/disable pay button based on selection
                const payButton = $('#payButton');
                if (selectedCourses.length > 0) {
                    payButton.prop('disabled', false);
                } else {
                    payButton.prop('disabled', true);
                }
            }

            // Handle form submission
            $('#enrollmentForm').submit(function(e) {
                e.preventDefault();
                
                const selectedCourses = [];
                $('.course-checkbox:checked').each(function() {
                    const card = $(this).closest('.course-card');
                    selectedCourses.push({
                        course_name: card.data('course-name'),
                        department: card.find('p.text-muted').text()
                    });
                });

                if (selectedCourses.length === 0) {
                    alert('Please select at least one course');
                    return;
                }

                // Show confirmation modal
                showConfirmation(selectedCourses);
            });
        });

        function showConfirmation(selectedCourses) {
            let total = 0;
            let confirmationHtml = '<div class="selected-courses">';
            
            selectedCourses.forEach(course => {
                const card = $(`.course-card[data-course-name="${course.course_name}"]`);
                const price = parseFloat(card.data('price'));
                total += price;
                
                confirmationHtml += `
                    <div class="mb-2">
                        <strong>${course.course_name}</strong>
                        <span class="float-end">$${price.toFixed(2)}</span>
                    </div>
                `;
            });

            confirmationHtml += `
                <hr>
                <div class="d-flex justify-content-between">
                    <strong>Total Amount:</strong>
                    <strong>$${total.toFixed(2)}</strong>
                </div>
            `;

            $('#confirmationDetails').html(confirmationHtml);
            $('#confirmationModal').show();
        }

        function closeConfirmation() {
            $('#confirmationModal').hide();
        }

        function confirmEnrollment() {
            const selectedCourses = [];
            $('.course-checkbox:checked').each(function() {
                const card = $(this).closest('.course-card');
                selectedCourses.push({
                    course_name: card.data('course-name'),
                    department: card.find('p.text-muted').text()
                });
            });

            const formData = {
                courses: selectedCourses
            };

            // Show loading state
            const payButton = $('#payButton');
            const buttonText = payButton.find('.button-text');
            const spinner = payButton.find('.spinner-border');
            
            buttonText.text('Processing...');
            spinner.removeClass('d-none');
            payButton.prop('disabled', true);

            // Send enrollment request
            $.ajax({
                url: 'enroll_course.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.status === 'success') {
                            alert('Successfully enrolled in courses!');
                            window.location.href = 'my_courses.php';
                        } else {
                            alert('Error: ' + result.message);
                            resetButton();
                        }
                    } catch (e) {
                        alert('Error processing response. Please try again.');
                        resetButton();
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred. Please try again.');
                    console.error('Error:', error);
                    resetButton();
                }
            });

            function resetButton() {
                buttonText.text('Pay & Enroll Now');
                spinner.addClass('d-none');
                payButton.prop('disabled', false);
            }
        }
    </script>
</body>
</html> 