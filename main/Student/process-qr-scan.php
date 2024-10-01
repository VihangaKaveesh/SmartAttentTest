<?php
session_start();
include '../db.php';

// Configure SMTP settings directly in the script
// ini_set('SMTP', 'localhost'); // SMTP server
// ini_set('smtp_port', '25');   // SMTP port
// ini_set('sendmail_from', 'no-reply@example.com'); // sender email

// Ensure POST data is received
if (isset($_POST['qr_data']) && isset($_POST['geoLocation'])) {
    $qr_data = json_decode($_POST['qr_data'], true); // Decode the JSON QR data 

    // Debug: output the QR data to ensure correct structure
    echo "<pre>QR Data: ";
    print_r($qr_data);
    echo "</pre>";

    if (!$qr_data) {
        die("QR data could not be decoded.");
    }

    $student_location = $_POST['geoLocation'];
    $student_id = $_SESSION['student_id'];
    $qr_class_id = $qr_data['class_id'];
    $qr_timestamp = $qr_data['timestamp'];
    $qr_geo_location = explode(',', $qr_data['geo_location']);

    // Store class ID in session for later verification
    $_SESSION['class_id'] = $qr_class_id;

    // Step 1: Check if the QR code is expired (valid for 1 minute)
    // $current_time = time();
    // if (($current_time - $qr_timestamp) > 60) {
    //     die("QR code expired.");
    // } else {
    //     echo "QR code is valid.<br>";
    // }

    // Step 2: Check if the student has already scanned within 15 minutes
    $query = "SELECT attendance_time FROM attendance WHERE student_id = ? ORDER BY attendance_time DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result && (time() - strtotime($result['attendance_time']) < 900)) {
        die("You must wait 15 minutes to scan another QR code.");
    } else {
        echo "No recent scans within 15 minutes.<br>";
    }

    // Step 3: Check if student is within 10 meters of the QR code location
    list($qr_lat, $qr_lon) = $qr_geo_location;
    list($student_lat, $student_lon) = explode(',', $student_location);

    // Function to calculate the distance between two coordinates
    function getDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;
        return $distance;
    }

    $distance = getDistance($qr_lat, $qr_lon, $student_lat, $student_lon);
    if ($distance > 10) {
        die("You are too far from the location.");
    } else {
        echo "Student is within 10 meters of the QR code location.<br>";
    }

    // Step 4: Fetch student's email from the database
    $query = "SELECT email FROM students WHERE student_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result) {
        die("Student not found.");
    }
    $student_email = $result['email'];
    echo "Student email fetched successfully.<br>";

    // Step 5: Generate a two-step email verification code
    $verification_code = rand(100000, 999999);
    $_SESSION['verification_code'] = $verification_code;
    echo "Verification code generated.<br>";

    // Step 6: Send the verification email to the student
    $to = $student_email;
    $subject = "QR Code Attendance Verification";
    $message = "Hello, please use the following code to verify your QR code attendance: $verification_code";
    $headers = "From: no-reply@example.com";

    // Enable error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    if (mail($to, $subject, $message, $headers)) {
        echo "Verification email sent successfully. Redirecting to email verification page.<br>";
        echo "verify-email.php"; // Success, notify JS code to redirect to email verification
    } else {
        die("Error sending verification email. Check SMTP settings and ensure the mail server is running.");
    }
} else {
    die("Required data not received.");
}
?>
