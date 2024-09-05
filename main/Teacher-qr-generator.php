<?php
// Start session
session_start();

// Database connection
include 'db.php';

// Check if the user is a teacher
// if ($_SESSION['role'] != 'teacher') {
//     die("Access Denied");
// }

// Fetch the classes from the 'classes' table
$teacher_id = $_SESSION['teacher_id'];
$query = "SELECT class_id, class_name FROM classes WHERE teacher_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Generate QR Code</title>
</head>
<body>
    <form action="generate-qr-code.php" method="POST">
        <label for="class">Select Class:</label>
        <!-- classes that the teacher is assigned for will be displayed in a dropdownlist -->
        <select name="class_id" required>
            <?php
            while ($row = $result->fetch_assoc()) {
                echo "<option value='{$row['class_id']}'>{$row['class_name']}</option>";
            }
            ?>
        </select>

        <input type="hidden" id="geoLocation" name="geoLocation">
        <button type="submit">Generate QR Code</button>
    </form>

    <script>

    // Get the teacher's current geolocation and store it in a hidden field
    navigator.geolocation.getCurrentPosition(function(position) {
        document.getElementById('geoLocation').value = position.coords.latitude + ',' + position.coords.longitude;
    });
    </script>
</body>
</html>
