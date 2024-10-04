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
    echo "<div class='qr-container'>";
echo "<h1>Session QR Code</h1>";
echo "<img src='{$qrCodeFilePath}' alt='QR Code' />";
echo "<p>Session recorded successfully!</p>";
echo "</div>";
} else {
    echo "Error recording session: " . $stmt->error;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Generate QR Code</title>
    <style>
      /* General Styling */
body {
    font-family: 'Roboto', sans-serif;
    background-color: #f0f4f8;
    color: #333;
    margin: 0;
    padding: 20px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    height: 100vh;
    box-sizing: border-box;
}

/* Card Container for QR Code */
.qr-container {
    background-color: #fff;
    border-radius: 12px;
    box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.1);
    padding: 20px;
    text-align: center;
    max-width: 400px;
    width: 100%;
    margin: 0 auto;
}

.qr-container img {
    width: 100%;
    max-width: 250px;
    height: auto;
    margin-bottom: 20px;
}

.qr-container h1 {
    font-size: 1.5rem;
    margin-bottom: 10px;
    color: #0a72b1;
}

.qr-container p {
    font-size: 1rem;
    color: #666;
    margin-bottom: 20px;
}

/* Button Styles */
.qr-container button {
    background-color: #0a72b1;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 12px 24px;
    font-size: 1rem;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.qr-container button:hover {
    background-color: #055a88;
}

/* Mobile Responsiveness */
@media (max-width: 600px) {
    body {
        padding: 10px;
    }

    .qr-container {
        padding: 15px;
    }

    .qr-container h1 {
        font-size: 1.2rem;
    }

    .qr-container p {
        font-size: 0.9rem;
    }

    .qr-container button {
        padding: 10px 20px;
        font-size: 0.9rem;
    }
}
    </style>
</head>
</html>