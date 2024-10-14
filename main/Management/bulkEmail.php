<?php
session_start();
include '../db.php'; // Include your database connection

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../phpmailer/src/Exception.php';
require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';

// Check if the management or admin is logged in
if ($_SESSION['role'] != 'management') {
    echo "<div class='error-message'>Access Denied</div>";
    exit;
}

// Get ModuleID from GET request
$module_id = isset($_GET['module_id']) ? $_GET['module_id'] : null;

if (!$module_id) {
    echo "<div class='error-message'>Module ID not provided</div>";
    exit;
}

// Fetch the email addresses of all students following the given module
$query = "SELECT Email FROM Students WHERE ModuleID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $module_id);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row['Email'];
}

// Check if students exist
if (empty($students)) {
    echo "<div class='error-message'>No students found for the given module.</div>";
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject = $_POST['subject'];
    $body = $_POST['message'];

    // Loop through students' emails and send the email to each one using PHPMailer
    foreach ($students as $email) {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP(); // Set mailer to use SMTP
            $mail->Host       = 'smtp.gmail.com'; // Specify SMTP server
            $mail->SMTPAuth   = true; // Enable SMTP authentication
            $mail->Username   = 'blacksnow2k03@gmail.com'; // SMTP username
            $mail->Password   = 'ylclucejxyvkronc'; // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
            $mail->Port       = 587; // TCP port to connect to

            // Recipients
            $mail->setFrom('blacksnow2k03@gmail.com', 'Attendance System'); // Set sender's email
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->SMTPDebug = 0; // Set to 2 for verbose debug output
            $mail->send();
        } catch (Exception $e) {
            echo "<div class='error-message'>Message could not be sent to {$email}. Mailer Error: {$mail->ErrorInfo}</div>"; // Error message
        }
    }

    echo "<div class='success-message'>Emails have been sent to all students following the module.</div>";
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Email to Students</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <style>
         /* Hamburger Menu Icon */
         .hamburger {
            font-size: 2rem;
            cursor: pointer;
            margin: 10px;
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 2000;
        }

        /* Sidebar Styling */
        .sidebar {
            position: fixed;
            top: 0;
            left: -100%;
            height: 100%;
            width: 100vw;
            background-color:#a03aba;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            transition: left 0.4s ease;
            z-index: 1500;
        }

        .sidebar.active {
            left: 0;
        }

        .nav-links a {
            color: white;
            padding: 20px;
            margin: 10px 0;
            text-decoration: none;
            font-weight: 500;
            font-size: 1.5rem;
            font-family: 'Poppins', sans-serif;
            text-align: center;
            width: 100%;
            transition: background 0.3s, padding 0.3s, transform 0.3s ease;
            position: relative;
        }

        /* Modern Hover Animation */
        .nav-links a::before {
            content: '';
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            height: 3px;
            background: #fff;
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.3s ease;
        }

        .nav-links a:hover::before {
            transform: scaleX(1);
            transform-origin: left;
        }

        .nav-links a:hover {
            background-color: #d448f7;
            border-radius: 5px;
            transform: translateY(-5px);
        }

        /* Basic styling for the form */
form {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    font-family: Arial, sans-serif;
}

/* Style for the form heading */
h1 {
    text-align: center;
    color: #333;
    font-size: 24px;
    margin-bottom: 20px;
}

/* Styling for labels */
label {
    font-weight: bold;
    color: #333;
    margin-bottom: 8px;
    display: inline-block;
}

/* Styling for input fields and textarea */
input[type="text"], 
textarea {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    margin-bottom: 20px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 16px;
    box-sizing: border-box;
}

/* Styling for the submit button */
button[type="submit"] {
    width: 100%;
    padding: 12px;
    background-color: #a03aba;
    border: none;
    border-radius: 4px;
    color: white;
    font-size: 18px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

/* Button hover effect */
button[type="submit"]:hover {
    background-color: #218838;
}

/* Error message styling */
.error-message {
    color: #ff4d4d;
    background-color: #ffe6e6;
    padding: 10px;
    border: 1px solid #ff4d4d;
    border-radius: 4px;
    margin-bottom: 20px;
    font-weight: bold;
    text-align: center;
    font-family: Arial, sans-serif;
}

/* Success message styling */
.success-message {
    color: #28a745;
    background-color: #d4edda;
    padding: 10px;
    border: 1px solid #28a745;
    border-radius: 4px;
    margin-bottom: 20px;
    font-weight: bold;
    text-align: center;
    font-family: Arial, sans-serif;
}

/* Responsive design for smaller screens */
@media (max-width: 768px) {
    form {
        padding: 15px;
    }

    input[type="text"],
    textarea {
        font-size: 14px;
    }

    button[type="submit"] {
        font-size: 16px;
    }
}
    </style>
</head>
<body>
 <!-- Hamburger Icon -->
<div class="hamburger">
    <i class="fas fa-bars"></i>
</div>

<!-- Sidebar Menu -->
<div class="sidebar">
    <div class="nav-links">
        <a href="manageStudents.php">Students</a><br><br><br><br><br>
        <a href="addModules.php">Modules</a><br><br><br><br><br>
        <a href="manageTeachers.php">Teachers</a><br><br><br><br><br>
        <a href="notice.php">Notices</a><br><br><br><br><br>
        <a href="addLabs.php">Labs</a><br><br><br><br><br>
        <a href="../login/login.html">Logout</a>
    </div>
</div>

    <h1>Send General Notice</h1>
    <form method="post" action="">
        <label for="subject">Subject:</label><br>
        <input type="text" id="subject" name="subject" required><br><br>

        <label for="message">Message:</label><br>
        <textarea id="message" name="message" rows="10" required></textarea><br><br>

        <button type="submit">Send Email</button>
    </form>
    <script>
    // Toggle Sidebar
    const hamburger = document.querySelector('.hamburger');
    const sidebar = document.querySelector('.sidebar');

    hamburger.addEventListener('click', function() {
        sidebar.classList.toggle('active');
    });
</script>
</body>
</html>
