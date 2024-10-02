<?php
session_start();
include '../db.php';

// Ensure the student is logged in
if ($_SESSION['role'] != 'student') {
    die("Access Denied");
}

// Check for previous scans within 15 minutes
$current_time = date('Y-m-d H:i:s');
$check_query = "SELECT COUNT(*) FROM Attendance WHERE StudentID = ? AND SessionID = ? AND TIMESTAMPDIFF(MINUTE, AttendanceTime, ?) < 15";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("iis", $student_id, $qr_data['session_id'], $current_time);
$stmt->execute();
$count = $stmt->get_result()->fetch_row()[0];

if ($count > 0) {
    die("You have already scanned a QR code in the last 15 minutes.");
}

// Get QR data and geolocation from POST request
$qr_data = json_decode($_POST['qr_data'], true);
$geoLocation = $_POST['geoLocation'];
$latitude = explode(',', $geoLocation)[0];
$longitude = explode(',', $geoLocation)[1];

// Fetch lab location based on lab_id from QR code data
$lab_id = $qr_data['lab_id'];
$query = "SELECT Latitude, Longitude FROM Labs WHERE LabID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $lab_id);
$stmt->execute();
$lab_location = $stmt->get_result()->fetch_assoc();

if (!$lab_location) {
    die("Lab not found.");
}

// Calculate distance (in meters) using Haversine formula
$earth_radius = 6371000; // Earth's radius in meters
$lat1 = deg2rad($lab_location['Latitude']);
$lon1 = deg2rad($lab_location['Longitude']);
$lat2 = deg2rad($latitude);
$lon2 = deg2rad($longitude);

$d_lat = $lat2 - $lat1;
$d_lon = $lon2 - $lon1;

$a = sin($d_lat / 2) * sin($d_lat / 2) + cos($lat1) * cos($lat2) * sin($d_lon / 2) * sin($d_lon / 2);
$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
$distance = $earth_radius * $c;

if ($distance > 10) {
    die("You are too far from the lab location.");
}

// Fetch student's email
$student_id = $_SESSION['student_id'];
$query = "SELECT Email FROM Students WHERE StudentID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    die("Student not found.");
}

// Generate a 2-step verification code
$verification_code = rand(100000, 999999);
$_SESSION['verification_code'] = $verification_code;

// Send verification code to student's email
$to = $student['Email'];
$subject = "QR Code Attendance Verification";
$message = "Your verification code is: $verification_code";
$headers = "From: no-reply@yourdomain.com\r\n";

// if (mail($to, $subject, $message, $headers)) {
//     echo "Verification code sent to your email.<br>";
    echo "Your verification code for testing purposes is: <strong>$verification_code</strong><br>";
// } else {
//     die("Failed to send verification code.");
// }



// Display verification form
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Attendance</title>
</head>
<body>
    <form action="submit-verification.php" method="POST">
        <label for="verification_code">Enter Verification Code:</label>
        <input type="text" name="verification_code" required>
        <input type="hidden" name="session_id" value="<?php echo $qr_data['session_id']; ?>">
        <button type="submit">Verify</button>
    </form>
</body>
</html>
