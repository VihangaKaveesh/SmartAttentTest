<?php
session_start();
include 'phpqrcode/qrlib.php'; // Assuming you use the 'phpqrcode' library

// Database connection
include 'db.php';

// Check if the user is a teacher
if ($_SESSION['role'] != 'teacher') {
    die("Access Denied");
}

// Get class details and geolocation
$class_id = $_POST['class_id'];
$geoLocation = $_POST['geoLocation'];
$timestamp = time();

// Calculate expiration time (1 minute from the time qr code is initiated)
$expiration_time = date('Y-m-d H:i:s', $timestamp + 60);

// Store QR code data in the database
$query = "INSERT INTO qr_codes (class_id, geo_location, expires_at) VALUES (?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $class_id, $geoLocation, $expiration_time);
$stmt->execute();
$qr_code_id = $stmt->insert_id;

// QR code content 
$qr_content = json_encode([
    'class_id' => $class_id,
    'timestamp' => $timestamp,
    'geo_location' => $geoLocation
]);

// Generate the QR code image
QRcode::png($qr_content, "qrcodes/qr_{$qr_code_id}.png");

// Display the QR code image
echo "<img src='qrcodes/qr_{$qr_code_id}.png' />";
?>
