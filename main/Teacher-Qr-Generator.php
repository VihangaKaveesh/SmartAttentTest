<?php
include 'db.php';
include 'phpqrcode/qrlib.php'; // Include the QR code library

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $teacher_id = $_POST['teacher_id'];
    $subject = $_POST['subject'];
    $class_name = $_POST['class_name'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];

    // Auto-generate the lecture start time
    $start_time = date("Y-m-d H:i:s");

    // Generate a unique QR code content
    $qr_content = $teacher_id . '|' . $subject . '|' . $class_name . '|' . $start_time . '|' . $latitude . ',' . $longitude;
    
    // Save the QR code image file
    $qr_file = 'qrcodes/' . uniqid() . '.png';
    QRcode::png($qr_content, $qr_file, QR_ECLEVEL_L, 10);

    // Insert QR code details into the database
    $stmt = $conn->prepare("INSERT INTO qr_codes (teacher_id, qr_code, subject, class_name, start_time, latitude, longitude, created_at, expiration_time) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 2 MINUTE))");
    $stmt->bind_param("issssss", $teacher_id, $qr_file, $subject, $class_name, $start_time, $latitude, $longitude);
    $stmt->execute();

    echo "<h2>QR Code Generated</h2>";
    echo "<p>Subject: $subject</p>";
    echo "<p>Class: $class_name</p>";
    echo "<p>Lecture Start Time: $start_time</p>";
    echo "<p>Location: $latitude, $longitude</p>";
    echo "<p><img src='$qr_file' alt='QR Code'></p>";
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Login</title>
    <script>
        function getGeolocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    document.getElementById('latitude').value = position.coords.latitude;
                    document.getElementById('longitude').value = position.coords.longitude;
                });
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        }

        window.onload = getGeolocation;
    </script>
</head>
<body>
    <h2>Teacher Login</h2>
    <form action="generate_qr.php" method="post">
        <label for="teacher_id">Teacher ID:</label>
        <input type="text" id="teacher_id" name="teacher_id" required><br><br>

        <label for="subject">Subject:</label>
        <input type="text" id="subject" name="subject" required><br><br>

        <label for="class_name">Class Name:</label>
        <input type="text" id="class_name" name="class_name" required><br><br>

        <!-- Latitude and Longitude will be auto-filled -->
        <input type="hidden" id="latitude" name="latitude" required>
        <input type="hidden" id="longitude" name="longitude" required>

        <input type="submit" value="Generate QR Code">
    </form>
</body>
</html>



