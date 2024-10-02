<?php
// teacher-qr-generator.php
session_start();

// Database connection
include '../db.php';

// Check if the user is a teacher
if ($_SESSION['role'] != 'teacher') {
    die("Access Denied");
}

// Fetch the modules that the teacher is assigned to
$teacher_id = $_SESSION['teacher_id'];
$query = "SELECT ModuleID, ModuleName FROM Modules WHERE TeacherID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$modules_result = $stmt->get_result();

// Fetch the labs available
$query = "SELECT LabID, LabName FROM Labs";
$stmt = $conn->prepare($query);
$stmt->execute();
$labs_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Generate QR Code</title>
</head>
<body>
    <form action="generate-qr-code.php" method="POST">
        <label for="module">Select Module:</label>
        <select name="module_id" required>
            <?php
            while ($row = $modules_result->fetch_assoc()) {
                echo "<option value='{$row['ModuleID']}'>{$row['ModuleName']}</option>";
            }
            ?>
        </select>

        <label for="lab">Select Lab:</label>
        <select name="lab_id" required>
            <?php
            while ($row = $labs_result->fetch_assoc()) {
                echo "<option value='{$row['LabID']}'>{$row['LabName']}</option>";
            }
            ?>
        </select>

        <button type="submit">Generate QR Code</button>
    </form>
</body>
</html>
