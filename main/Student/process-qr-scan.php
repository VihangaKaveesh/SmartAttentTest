<?php
session_start(); // Start the session
include '../db.php'; // Include database connection
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../phpmailer/src/Exception.php'; // Include PHPMailer's exception handling
require '../phpmailer/src/PHPMailer.php'; // Include PHPMailer's main class
require '../phpmailer/src/SMTP.php'; // Include PHPMailer's SMTP class

// Ensure the student is logged in
if ($_SESSION['role'] != 'student') {
    showError("Access Denied");
}

// Function to display error and redirect
function showError($message) {
    echo "<div style='color: white; background-color: red; padding: 10px; border-radius: 5px; text-align: center;'>$message</div>";
    echo "<script>setTimeout(function(){ window.location.href = 'qr-scanner.html'; }, 3000);</script>"; // Redirect after 3 seconds
    exit();
}

// Get the student ID from session
$student_id = $_SESSION['student_id'];

// Get QR data and geolocation from POST request
$qr_data = json_decode($_POST['qr_data'], true); // Decode JSON data from QR
$geoLocation = $_POST['geoLocation']; // Get geolocation from POST
$latitude = explode(',', $geoLocation)[0]; // Extract latitude
$longitude = explode(',', $geoLocation)[1]; // Extract longitude

// Extract ModuleID from the QR data
$qr_module_id = $qr_data['module_id']; // Assuming the QR data includes 'module_id'

// Fetch the student's ModuleID from the database
$query = "SELECT ModuleID FROM Students WHERE StudentID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id); // Bind student_id parameter
$stmt->execute();
$student_module = $stmt->get_result()->fetch_assoc();

if (!$student_module) {
    showError("Student not found.");
}

// Check if the student's ModuleID matches the ModuleID in the QR code
if ($student_module['ModuleID'] != $qr_module_id) {
    showError("You are not enrolled in this module.");
}

// Check for previous scans within 15 minutes
$current_time = date('Y-m-d H:i:s'); // Get current time
$check_query = "SELECT COUNT(*) FROM Attendance WHERE StudentID = ? AND SessionID = ? AND TIMESTAMPDIFF(MINUTE, AttendanceTime, ?) < 15";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("iis", $student_id, $qr_data['session_id'], $current_time); // Bind parameters
$stmt->execute();
$count = $stmt->get_result()->fetch_row()[0]; // Fetch count of recent scans

if ($count > 0) {
    showError("You have already scanned a QR code in the last 15 minutes.");
}

// Fetch lab location based on lab_id from QR code data
$lab_id = $qr_data['lab_id'];
$query = "SELECT Latitude, Longitude FROM Labs WHERE LabID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $lab_id);
$stmt->execute();
$lab_location = $stmt->get_result()->fetch_assoc();

if (!$lab_location) {
    showError("Lab not found.");
}

// Calculate distance (in meters) using Haversine formula
$earth_radius = 6371000; // Earth's radius in meters
$lat1 = deg2rad($lab_location['Latitude']); // Convert latitude to radians
$lon1 = deg2rad($lab_location['Longitude']); // Convert longitude to radians
$lat2 = deg2rad($latitude); // Convert scanned latitude to radians
$lon2 = deg2rad($longitude); // Convert scanned longitude to radians

$d_lat = $lat2 - $lat1; // Difference in latitude
$d_lon = $lon2 - $lon1; // Difference in longitude

// Haversine formula to calculate distance
$a = sin($d_lat / 2) * sin($d_lat / 2) + cos($lat1) * cos($lat2) * sin($d_lon / 2) * sin($d_lon / 2);
$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
$distance = $earth_radius * $c; // Distance in meters

if ($distance > 10) {
    showError("You are too far from the lab location.");
}

// Fetch student's email
$query = "SELECT Email FROM Students WHERE StudentID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    showError("Student not found.");
}

// Generate a 2-step verification code
$verification_code = rand(100000, 999999); // Generate random verification code
$_SESSION['verification_code'] = $verification_code; // Store verification code in session
$_SESSION['session_id'] = $qr_data['session_id']; // Store session_id in session

// Send verification code to student's email using PHPMailer
$mail = new PHPMailer(true); // Instantiate PHPMailer

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
    $mail->setFrom('blacksnow2k03@gmail.com', 'Attendance System');
    $mail->addAddress($student['Email']);

    // Content
    $mail->isHTML(true);
    $mail->Subject = "QR Code Attendance Verification";
    $mail->Body    = "Your verification code is: <strong>$verification_code</strong>";

    $mail->SMTPDebug = 0;
    $mail->send();

    $_SESSION['email_sent'] = true; // Email sent status

    // Redirect to verification form
    header("Location: submit-verification.php");
    exit();
} catch (Exception $e) {
    showError("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
}
?>
