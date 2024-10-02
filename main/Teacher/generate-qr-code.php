<?php
// generate-qr-code.php
session_start();

// Database connection
include '../db.php';

// Ensure the teacher is logged in
if ($_SESSION['role'] != 'teacher') {
    die("Access Denied");
}

// Get module and lab selections
$module_id = $_POST['module_id'];
$lab_id = $_POST['lab_id'];
$teacher_id = $_SESSION['teacher_id'];

// Fetch the lab's location
$query = "SELECT Latitude, Longitude FROM Labs WHERE LabID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $lab_id);
$stmt->execute();
$lab_result = $stmt->get_result()->fetch_assoc();

if (!$lab_result) {
    die("Lab not found.");
}

// Insert session details into the Sessions table
$query = "INSERT INTO Sessions (TeacherID, ModuleID, LabID) VALUES (?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $teacher_id, $module_id, $lab_id);

if ($stmt->execute()) {
    $session_id = $stmt->insert_id; // Get the newly created SessionID

    // Generate QR code data with SessionID
    $qr_data = [
        'module_id' => $module_id,
        'lab_id' => $lab_id,
        'session_id' => $session_id, // Include the SessionID
        'timestamp' => date('Y-m-d H:i:s')
    ];

    // Log QR data for debugging
    error_log("QR Data for Code: " . json_encode($qr_data));

    // Generate QR code using PHP QR Code library
    include '../phpqrcode/qrlib.php'; // Adjusted path as necessary

    // Set the path where the QR code will be saved
    $qrCodeFilePath = '../qrcodes/qr-code.png';

    // Generate the QR code image
    QRcode::png(json_encode($qr_data), $qrCodeFilePath, QR_ECLEVEL_L, 10);

    // Redirect or display the QR code image
    echo "<img src='{$qrCodeFilePath}' alt='QR Code' />";
    echo "Session recorded successfully! QR Code generated with SessionID: $session_id.";
} else {
    echo "Error recording session: " . $stmt->error;
}
?>
