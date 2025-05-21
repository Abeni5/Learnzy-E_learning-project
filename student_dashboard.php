<?php
session_start();
include 'db_connection.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.html");
    exit;
}

// Get all courses grouped by category
$stmt = $conn->prepare("
    SELECT c.*, 
           (SELECT COUNT(*) FROM course_enrollments WHERE course_id = c.id AND student_id = ?) as is_enrolled
    FROM courses c 
    ORDER BY c.category, c.title
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$courses = $result->fetch_all(MYSQLI_ASSOC);

// Group courses by category
$categories = [];
foreach ($courses as $course) {
    $categories[$course['category']][] = $course;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <meta charset="UTF-8" />
  <title>Student Dashboard</title>
  <!-- Add Stripe.js -->
  <script src="https://js.stripe.com/v3/"></script>
  <style>
    * { 
      margin: 0; 
      padding: 0; 
      box-sizing: border-box; 
    }
    html {
      scroll-behavior: smooth;
    }
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: url('image/background.png') no-repeat center center fixed;
      background-size: cover;
      color: white;
    }
    .overlay {
      background-color: rgba(0, 0, 0, 0.8);
      min-height: 100vh;
    }
    .navbar {
      position: sticky;
      top: 0;
      z-index: 1000;
      background: linear-gradient(135deg,rgb(137, 144, 223), #0d47a1);
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      box-shadow: 0 2px 15px rgba(0,0,0,0.2);
    }
    .navbar a {
      color: white;
      text-decoration: none;
      margin-left: 20px;
      font-weight: 500;
      transition: all 0.3s ease;
      padding: 8px 15px;
      border-radius: 6px;
    }
    .navbar a:hover {
      background: rgba(255,255,255,0.1);
      transform: translateY(-2px);
    }
    .navbar input[type="text"] {
      padding: 10px 15px;
      border-radius: 8px;
      border: none;
      width: 200px;
      background: rgba(255,255,255,0.1);
      color: white;
      transition: all 0.3s ease;
    }
    .navbar input[type="text"]::placeholder {
      color: rgba(255,255,255,0.7);
    }
    .navbar input[type="text"]:focus {
      background: rgba(255,255,255,0.2);
      outline: none;
      width: 250px;
    }
    .section {
      padding: 80px 20px;
      min-height: 100vh;
      text-align: center;
    }
    .section h2 {
      font-size: 2.5rem;
      margin-bottom: 30px;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }
    .card {
      background: rgba(255,255,255,0.95);
      color: #333;
      padding: 25px;
      margin: 20px auto;
      border-radius: 15px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.2);
      transition: all 0.3s ease;
      backdrop-filter: blur(10px);
    }
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    }
    .dropdown {
      display: none;
      position: absolute;
      top: 140px;
      left: 20px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.2);
      z-index: 1000;
      min-width: 250px;
      overflow: hidden;
    }
    .dropdown div {
      padding: 15px;
      border-bottom: 1px solid #eee;
      color: #333;
      transition: all 0.3s ease;
    }
    .dropdown div:hover {
      background: #f8f9fa;
    }
    .dropdown button {
      margin-top: 10px;
      padding: 8px 15px;
      background:rgb(149, 155, 227);
      border: none;
      color: white;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    .dropdown button:hover {
      background:rgb(142, 153, 239);
      transform: translateY(-2px);
    }
    .search-form {
      display: flex;
      justify-content: center;
      margin-bottom: 30px;
      gap: 15px;
    }
    .search-form input {
      padding: 12px 20px;
      width: 350px;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      background: rgba(255,255,255,0.95);
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
    }
    .search-form input:focus {
      outline: none;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
      transform: translateY(-2px);
    }
    .search-form button {
      padding: 12px 25px;
      background: #1a237e;
      border: none;
      color: white;
      border-radius: 8px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: all 0.3s ease;
      font-weight: 500;
    }
    .search-form button:hover {
      background: #283593;
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    .search-form button i {
      font-size: 1.2rem;
    }
    #paymentModal {
      background: rgba(0,0,0,0.8);
      backdrop-filter: blur(5px);
    }
    #paymentModal > div {
      background: white;
      border-radius: 15px;
      padding: 30px;
      box-shadow: 0 4px 30px rgba(0,0,0,0.3);
    }
    #paymentModal button {
      transition: all 0.3s ease;
    }
    #paymentModal button:hover {
      transform: translateY(-2px);
    }
    footer {
      background: linear-gradient(135deg,rgb(116, 125, 220), #0d47a1);
      padding: 40px 20px;
    }
    footer a {
      transition: all 0.3s ease;
    }
    footer a:hover {
      transform: translateY(-3px);
    }
    @media (max-width: 768px) {
      .navbar {
        padding: 10px 15px;
      }
      .navbar a {
        margin: 5px;
        font-size: 0.9rem;
      }
      .search-form input {
        width: 100%;
        max-width: 300px;
      }
      .card {
        padding: 20px;
      }
      .section h2 {
        font-size: 2rem;
      }
    }
  </style>
</head>
<body>
  <div class="overlay">
  <div class="navbar">
  <div style="display: flex; align-items: center;gap:10px;">
  <img src="image/learnzy-logo.png" alt="Learnzy Logo" style="height: 80px;width:auto;">
</div>


  <div>
    <!-- Search Bar -->
    <form action="search.php" method="GET" class="search-form" style="margin: 0;">
      <input type="text" name="q" placeholder="Search courses..." required>
      <button type="submit">
        <i class="fas fa-search"></i>
        Search
      </button>
    </form>

    <!-- Navigation Links -->
    <a href="#home">Home </a>
    <a href="#courses">Courses</a>
    <a href="#about">About</a>
    <a href="#help">Help</a>
    <a href="howto.html" title="How it works" class="help-icon"><i class="fas fa-question-circle"></i></a>
 <!-- Profile Dropdown -->
 <div style="position: relative; display: inline-block;">
  <a href="#" onclick="toggleProfileDropdown()" style="cursor: pointer; color: white; font-weight: bold;">👤 Profile ⯆</a>
  <div id="profileDropdown" style="display: none; position: absolute; right: 0; top: 30px; background-color: #1e1e1e; color: #f1f1f1; min-width: 250px; border-radius: 8px; box-shadow: 0 6px 12px rgba(0,0,0,0.2); z-index: 1000; overflow: hidden;">

    <div style="padding: 12px; border-bottom: 1px solid #444;">
      <strong>🧑 Full Name:</strong><br>
      <?= isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'N/A' ?>
    </div>

    <div style="padding: 12px; border-bottom: 1px solid #444;">
      <strong>🔐 Username:</strong><br>
      <?= htmlspecialchars($_SESSION['username']) ?>
    </div>

    <div style="padding: 12px; border-bottom: 1px solid #444;">
      <strong>📧 Email:</strong><br>
      <?= isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'N/A' ?>
    </div>

    <div style="padding: 12px; border-bottom: 1px solid #444;">
      <strong>🆔 ID:</strong><br>
      <?= htmlspecialchars($_SESSION['user_id']) ?>
    </div>

    <div style="padding: 12px;">
      <strong>📘 Selected Courses:</strong>
      <ul id="selectedCoursesList" style="margin-top: 5px; padding-left: 20px; font-size: 0.9rem; color: #ccc;"></ul>
    </div>
    <a href="my_courses.php" style="display: block; padding: 12px; text-align: center; color: #3498db;">📚 My Courses</a>
    <a href="logout.php" style="display: block; padding: 12px; background-color: #e74c3c; text-align: center; color: white; text-decoration: none;">
      🚪 Logout
    </a>
  </div>
</div>


    <a href="logout.php">Logout</a>
  </div>
</div>

<!-- Home -->
<section id="home" class="section" style="display: flex; align-items: center; justify-content: center; text-align: center;">
  <div class="hero-content">
    <h1 style="font-size: 3rem; margin-bottom: 20px;">Welcome to Your Learning Journey</h1>
    <p style="font-size: 1.2rem; margin-bottom: 30px;">
      Explore courses, gain skills, and unlock your potential anytime, anywhere.
    </p>
    <a href="#courses" class="btn" 
       style="padding: 12px 25px; background-color: #3498db; color: white; text-decoration: none; border-radius: 5px; font-size: 1rem;">
       Get Started
    </a>
  </div>
</section>

<!-- Courses -->
<section id="courses" class="section">
  <h2 style="text-align: center; margin-bottom: 40px;">COURSES</h2>

  <div style="display: flex; justify-content: center; flex-wrap: wrap; gap: 30px;">
    <?php foreach ($categories as $category => $categoryCourses): ?>
      <div class="card" style="position: relative; width: 250px; padding: 20px; border: 1px solid #ccc; border-radius: 8px;">
        <h3 style="color: #3498db;"><?= htmlspecialchars($category) ?></h3>
        <p><?= count($categoryCourses) ?> courses available</p>
        <button onclick="toggleDropdown('<?= strtolower(str_replace(' ', '', $category)) ?>Dropdown')" 
                style="margin-top: 10px; background: #3498db; color: white; padding: 10px 15px; border: none; border-radius: 5px;">
          View Courses
        </button>
        <div id="<?= strtolower(str_replace(' ', '', $category)) ?>Dropdown" class="dropdown">
          <?php foreach ($categoryCourses as $course): ?>
            <div>
              <strong><?= htmlspecialchars($course['title']) ?></strong>
              <?php if ($course['is_enrolled']): ?>
                <span class="enrolled-badge">Enrolled</span>
              <?php endif; ?>
              <br>
              <small>💲<?= number_format($course['price'], 2) ?></small>
              <br>
              <?php if (!$course['is_enrolled']): ?>
                <button onclick="markCourse(this, <?= $course['id'] ?>, '<?= htmlspecialchars($course['title']) ?>', <?= $course['price'] ?>)">
                  Select
                </button>
              <?php else: ?>
                <a href="view_course.php?course=<?= $course['id'] ?>" class="btn" style="display: inline-block; padding: 5px 10px; background: #3498db; color: white; text-decoration: none; border-radius: 4px;">
                  View Course
                </a>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Pay Now Button -->
  <div style="text-align: center; margin-top: 40px;">
    <button id="payButton" onclick="handlePayment()" 
            style="padding: 12px 30px; font-size: 1rem; background-color: #e74c3c; color: white; border: none; border-radius: 5px; cursor: pointer;">
      <span class="button-text">💳 Pay Now</span>
      <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
    </button>
    <p id="paymentMessage" style="margin-top: 15px; font-size: 1rem;"></p>
  </div>

<!-- Simple Payment Confirmation Modal -->
<div id="paymentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000;">
  <div style="position: relative; background: white; width: 90%; max-width: 500px; margin: 50px auto; padding: 20px; border-radius: 10px; color: #333;">
    <h2 style="margin-bottom: 20px;">Confirm Your Purchase</h2>
    <div id="orderSummary" style="margin-bottom: 20px; text-align: left;">
      <!-- Order summary will be inserted here -->
    </div>
    <button id="confirmPayment" style="width: 100%; padding: 12px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer;">
      <span class="button-text">Confirm Payment</span>
      <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
    </button>
    <button onclick="closePaymentModal()" style="width: 100%; padding: 12px; margin-top: 10px; background: #e74c3c; color: white; border: none; border-radius: 5px; cursor: pointer;">
      Cancel
    </button>
  </div>
</div>

</section>

<!-- About (Dark Theme) -->
<section id="about" class="section" style="background-color: #1c1c1e; color: #f1f1f1;">
  <div class="card" style="max-width: 1000px; margin: auto; padding: 40px 20px; background-color: #2c2c2e; border-radius: 16px; box-shadow: 0 0 20px rgba(0,0,0,0.5);">

    <div style="text-align: center; margin-bottom: 25px;">
      <img src="image/learnzy-logo.png" alt="Learnzy Logo" style="height: 90px;">
    </div>

    <h2 style="text-align: center; font-size: 2rem; margin-bottom: 15px; color:#f1f1f1;">Empowering You to Learn & Grow</h2>
    <p style="text-align: center; font-size: 1.1rem; color: #ccc; margin-bottom: 30px;">
      Learnzy is an online learning platform built to help students develop real-world skills through accessible, practical, and interactive courses.
    </p>

    <div style="display: flex; flex-wrap: wrap; gap: 30px; justify-content: center; align-items: center;">
      <div style="flex: 1 1 300px; text-align: left;">
        <h3 style="color: #4fa3ff;">🌍 Our Mission</h3>
        <ul style="line-height: 1.8; padding-left: 20px; color: #ddd;">
          <li>✅ Provide high-quality, affordable education for all</li>
          <li>✅ Make learning flexible, accessible, and fun</li>
          <li>✅ Equip learners with practical, job-ready skills</li>
        </ul>
      </div>
      <div style="flex: 1 1 300px; text-align: center;">
        <img src="image/about.png" alt="Learning Illustration" style="width: 100%; max-width: 350px; border-radius: 12px;">
      </div>
    </div>

    <p style="margin-top: 30px; text-align: center; font-style: italic; color: #aaa;">
      Education should unlock potential — not limit it. Let's grow together.
    </p>
  </div>
</section>

<!-- Help -->
<section id="help" class="section" style="background-color: rgba(0, 0, 0, 0.7); color: #fff;">
  <div class="card" style="max-width: 800px; margin: auto; padding: 40px; border-radius: 16px; background-color: rgba(30, 30, 30, 0.9); box-shadow: 0 4px 12px rgba(0,0,0,0.3);">

    <h2 style="text-align: center; font-size: 2rem; margin-bottom: 20px; color: #ffffff;">Need Help?</h2>
    <p style="text-align: center; font-size: 1.1rem; margin-bottom: 30px; color: #ccc;">
      Got a question, issue, or feedback? We're here to help you. Just drop us a message below!
    </p>

    <!-- Contact Form -->
    <form action="#" method="post" style="display: flex; flex-direction: column; gap: 15px;">
      <input type="text" name="name" placeholder="Your Name" required
             style="padding: 10px; font-size: 1rem; border-radius: 6px; border: none; background-color: #444; color: #fff;">

      <input type="email" name="email" placeholder="Your Email" required
             style="padding: 10px; font-size: 1rem; border-radius: 6px; border: none; background-color: #444; color: #fff;">

      <textarea name="message" rows="5" placeholder="Type your message here..." required
                style="padding: 10px; font-size: 1rem; border-radius: 6px; border: none; background-color: #444; color: #fff;"></textarea>

      <button type="submit"
              style="background-color: #3498db; color: white; font-size: 1rem; padding: 12px; border: none; border-radius: 6px; cursor: pointer;">
        📩 Send Message
      </button>
    </form>

    <p style="margin-top: 25px; text-align: center; font-style: italic; color: #bbb;">
      Or contact us directly: <strong>help@learnzy.com</strong>
    </p>

  </div>
</section>
<script>
let paidCourses = new Set(); // Shared memory for already paid courses
let selectedCourses = new Map(); // Store selected courses with their IDs and prices

// Load previously paid courses on page load
window.addEventListener('DOMContentLoaded', () => {
  fetch('get_paid_courses.php')
    .then(res => res.json())
    .then(courses => {
      const list = document.getElementById("selectedCoursesList");
      list.innerHTML = '';
      courses.forEach(course => {
        paidCourses.add(course.course_name);
        const li = document.createElement("li");
        li.innerHTML = `
          ${course.course_name}
          <a href="view_course.php?course=${course.course_id}" 
             style="margin-left: 10px; color: #3498db; text-decoration: underline; font-size: 0.85rem;" 
             target="_blank">
            View Course
          </a>`;
        list.appendChild(li);
      });
    });
});

function toggleDropdown(id) {
  const dropdown = document.getElementById(id);
  dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
}

function markCourse(button, courseId, courseName, price) {
  if (button.classList.contains("selected")) {
    // Unselect
    button.innerText = "Select";
    button.style.backgroundColor = "#2ecc71";
    button.classList.remove("selected");
    selectedCourses.delete(courseId);
  } else {
    if (paidCourses.has(courseName)) {
      alert(`⚠️ You have already paid for "${courseName}".`);
    } else {
      // Select
      button.innerText = "✅ Selected";
      button.style.backgroundColor = "#27ae60";
      button.classList.add("selected");
      selectedCourses.set(courseId, { name: courseName, price: price });
    }
  }
}

// Handle payment
function handlePayment() {
  const messageBox = document.getElementById("paymentMessage");

  if (selectedCourses.size === 0) {
    messageBox.style.color = "yellow";
    messageBox.innerText = "⚠️ Please select at least one course before paying.";
    return;
  }

  // Calculate total and prepare order summary
  let total = 0;
  let summary = '<h3>Order Summary:</h3><ul style="list-style: none; padding: 0;">';
  
  selectedCourses.forEach((course, courseId) => {
    total += course.price;
    summary += `<li style="margin-bottom: 10px;">${course.name} - $${course.price.toFixed(2)}</li>`;
  });
  
  summary += `</ul><div style="margin-top: 20px; font-weight: bold; border-top: 1px solid #ddd; padding-top: 10px;">
    Total Amount: $${total.toFixed(2)}</div>`;
  
  document.getElementById('orderSummary').innerHTML = summary;
  document.getElementById('paymentModal').style.display = 'block';
}

// Handle payment confirmation
document.getElementById('confirmPayment').addEventListener('click', async function() {
    const submitButton = this;
    const buttonText = submitButton.querySelector('.button-text');
    const spinner = submitButton.querySelector('.spinner-border');
    
    submitButton.disabled = true;
    buttonText.textContent = 'Processing...';
    spinner.classList.remove('d-none');

    try {
        const courses = Array.from(selectedCourses.entries()).map(([id, course]) => ({
            course_id: id,
            course_name: course.name,
            price: course.price
        }));

        const response = await fetch('enroll_course.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ courses: courses })
        });

        const result = await response.json();

        if (result.status === 'success') {
            const messageBox = document.getElementById("paymentMessage");
            messageBox.style.color = "lightgreen";
            messageBox.innerHTML = `
                <div style="background: rgba(46, 204, 113, 0.1); padding: 15px; border-radius: 5px; margin: 10px 0;">
                    <p>✅ ${result.message}</p>
                    <p style="margin-top: 10px;">Payment ID: ${result.data.payment_id}</p>
                    <p>Total Amount: $${result.data.total_amount}</p>
                </div>
            `;

            closePaymentModal();

            // Clear selected courses
            document.querySelectorAll("button.selected").forEach(btn => {
                btn.innerText = "Select";
                btn.classList.remove("selected");
                btn.style.backgroundColor = "#2ecc71";
            });

            // Add courses to paid courses set
            courses.forEach(course => {
                paidCourses.add(course.course_name);
            });

            // Update the selected courses list
            const list = document.getElementById("selectedCoursesList");
            courses.forEach(course => {
                const li = document.createElement("li");
                li.innerHTML = `
                    ${course.course_name}
                    <a href="view_course.php?course=${course.course_id}" 
                       style="margin-left: 10px; color: #3498db; text-decoration: underline; font-size: 0.85rem;" 
                       target="_blank">
                        View Course
                    </a>`;
                list.appendChild(li);
            });

            // Clear selected courses
            selectedCourses.clear();

            // Redirect to my courses page
            setTimeout(() => {
                window.location.href = 'my_courses.php';
            }, 2000);
        } else {
            throw new Error(result.message || 'Payment failed');
        }
    } catch (error) {
        const messageBox = document.getElementById("paymentMessage");
        messageBox.style.color = "red";
        messageBox.innerHTML = `
            <div style="background: rgba(231, 76, 60, 0.1); padding: 15px; border-radius: 5px; margin: 10px 0;">
                <p>❌ ${error.message}</p>
            </div>
        `;
        submitButton.disabled = false;
        buttonText.textContent = 'Confirm Payment';
        spinner.classList.add('d-none');
    }
});

// Close payment modal
function closePaymentModal() {
  document.getElementById('paymentModal').style.display = 'none';
}

// 👤 Profile dropdown toggle
function toggleProfileDropdown() {
  const dropdown = document.getElementById("profileDropdown");
  dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";
}

// 🧠 Global click to close dropdowns
window.addEventListener("click", function (e) {
  const isCardClick = e.target.closest(".card");
  const isProfileClick = e.target.closest("#profileDropdown") || e.target.closest('a[href="#"]');

  // Close all dropdowns
  document.querySelectorAll(".dropdown").forEach(d => {
    if (!isCardClick) d.style.display = "none";
  });

  // Close profile dropdown
  if (!isProfileClick) {
    document.getElementById("profileDropdown").style.display = "none";
  }
});
</script>


<!-- Footer -->
<footer style="background-color: #111; color: #ccc; text-align: center; padding: 30px 20px; font-size: 0.95rem;">
  <div style="margin-bottom: 20px;">
    <strong style="font-size: 1.1rem;">🌐 Follow Us</strong>
    <div style="margin-top: 10px; font-size: 1.5rem;">
      <a href="https://www.facebook.com/eabenezer1" target="_blank" style="margin: 0 12px; color: #3b5998;"><i class="fab fa-facebook-f"></i></a>
      <a href="https://x.com/AyeleAbenzer?t=5FARdL39ANwjfaNWBwMjfA&s=09" target="_blank" style="margin: 0 12px; color: #1da1f2;"><i class="fab fa-twitter"></i></a>
      <a href="https://www.instagram.com/abeni_burje?igsh=a3dkeGR0ZTJ6NHd6" target="_blank" style="margin: 0 12px; color: #e1306c;"><i class="fab fa-instagram"></i></a>
      <a href="abeni8952@gmail.com" style="margin: 0 12px; color: #f39c12;"><i class="fas fa-envelope"></i></a>
    </div>
  </div>

  <div style="margin-top: 25px;">
    <p style="margin-bottom: 8px;"><strong>👨‍💻 Founder</strong></p>
    <p style="color: #aaa;">Abenezer Ayele</p>
  </div>

  <hr style="margin: 25px auto; width: 60%; border: 1px solid #444;">

  <p style="color: #777;">© <?= date('Y') ?> <strong>Learnzy</strong>. All rights reserved.</p>
</footer>

</body>
</html>
