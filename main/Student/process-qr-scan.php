<?php
session_start();
include '../db.php';

// Ensure POST data is received
if (isset($_POST['qr_data']) && isset($_POST['geoLocation'])) {
    $qr_data = json_decode($_POST['qr_data'], true);
    $student_location = $_POST['geoLocation'];
    $student_id = $_SESSION['student_id'];
    $qr_class_id = $qr_data['class_id'];
    $qr_timestamp = $qr_data['timestamp'];
    $qr_geo_location = explode(',', $qr_data['geo_location']);

    // Store class ID in session for later verification
    $_SESSION['class_id'] = $qr_class_id;

    // Step 1: Check if the QR code is expired (valid for 1 minute)
    $current_time = time();
    if (($current_time - $qr_timestamp) > 60) {
        die("QR code expired.");
    }

    // Step 2: Check if the student has already scanned within 15 minutes
    $query = "SELECT attendance_time FROM attendance WHERE student_id = ? ORDER BY attendance_time DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result && (time() - strtotime($result['attendance_time']) < 900)) {
        die("You must wait 15 minutes to scan another QR code.");
    }

    // Step 3: Check if student is within 10m of the QR code location
    list($qr_lat, $qr_lon) = $qr_geo_location;
    list($student_lat, $student_lon) = explode(',', $student_location);

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
    }

    // Step 4: Fetch student email
    $query = "SELECT email FROM students WHERE student_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $student_email = $result['email'];

    // Step 5: Generate a two-step email verification code
    $verification_code = rand(100000, 999999);
    $_SESSION['verification_code'] = $verification_code;

    // Step 6: Send the verification email
    $to = $student_email;
    $subject = "QR Code Attendance Verification";
    $message = "Hello, please use the following code to verify your QR code attendance: $verification_code";
    $headers = "From: no-reply@example.com";

    if (mail($to, $subject, $message, $headers)) {
        echo "verify-email.php"; // Success, notify the JS code to redirect
    } else {
        die("Error sending verification email.");
    }
} else {
    die("Required data not received.");
}
?>
