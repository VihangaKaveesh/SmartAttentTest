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
    die("Access Denied");
}

// Get ModuleID from GET request
$module_id = isset($_GET['module_id']) ? $_GET['module_id'] : null;

if (!$module_id) {
    die("Module ID not provided");
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
    die("No students found for the given module.");
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
            echo "Message could not be sent to {$email}. Mailer Error: {$mail->ErrorInfo}"; // Error message
        }
    }

    echo "Emails have been sent to all students following the module.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Email to Students</title>
</head>
<body>
    <h1>Send General Notice</h1>
    <form method="post" action="">
        <label for="subject">Subject:</label><br>
        <input type="text" id="subject" name="subject" required><br><br>

        <label for="message">Message:</label><br>
        <textarea id="message" name="message" rows="10" required></textarea><br><br>

        <button type="submit">Send Email</button>
    </form>
</body>
</html>
