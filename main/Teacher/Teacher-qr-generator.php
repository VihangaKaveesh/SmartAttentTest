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
    <style>
        /* General styles for the form */
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    background-color: #f9f9f9;
}

form {
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    max-width: 400px;
    width: 90%;
}

label {
    font-weight: bold;
    margin-bottom: 10px;
    display: block;
}

select {
    width: 100%;
    padding: 10px;
    margin-bottom: 20px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 16px;
}

button {
    width: 100%;
    padding: 10px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 18px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

button:hover {
    background-color: #45a049;
}

/* Mobile styles */
@media (max-width: 600px) {
    form {
        padding: 15px;
    }

    label {
        font-size: 14px;
    }

    select, button {
        font-size: 14px;
        padding: 8px;
    }
}

/* Styles for larger screens (tablets and desktops) */
@media (min-width: 600px) and (max-width: 900px) {
    form {
        max-width: 600px;
    }
}

@media (min-width: 900px) {
    form {
        max-width: 500px;
    }

    button {
        font-size: 16px;
    }
}
    </style>
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
