<?php
session_start();

// Set the timezone to Sri Lanka
date_default_timezone_set('Asia/Colombo');

// Check if the user is logged in as a teacher
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login/login.html");
    exit();
}

// Function to connect to the database
function connectDB() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "smartattendtest";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

// Fetch module names and IDs from the modules table
function getModules($conn) {
    $sql = "SELECT ModuleID, ModuleName FROM modules"; // Adjust as necessary for your modules table
    $result = $conn->query($sql);

    $modules = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $modules[] = $row;
        }
    }
    return $modules;
}

// Check if the form is submitted
if (isset($_POST['submit'])) {
    $conn = connectDB();
    
    $moduleID = $_POST['moduleID'];
    $assignmentName = $_POST['assignmentName'];
    $description = $_POST['description'];
    $dueDate = $_POST['dueDate'];
    $handOutDate = date('Y-m-d H:i:s'); // Current date and time for handout in Sri Lanka Time

    // Handle file upload
    $targetDir = "uploads/";

    // Check if the uploads directory exists, create it if not
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true); // Create directory with permissions
    }

    $fileName = basename($_FILES["assignment"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

    // Only allow PDF files for assignment uploads
    if ($fileType === "pdf" && $_FILES["assignment"]["size"] <= 10000000) {
        // Upload the file to the server
        if (move_uploaded_file($_FILES["assignment"]["tmp_name"], $targetFilePath)) {
            // Insert assignment data into the database
            $sql = "INSERT INTO assignments (ModuleID, AssignmentName, Description, DueDate, Assignment, HandOutDate)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssss", $moduleID, $assignmentName, $description, $dueDate, $targetFilePath, $handOutDate);

            if ($stmt->execute()) {
                echo "Assignment uploaded successfully.";
            } else {
                echo "Error: " . $stmt->error;
            }
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    } else {
        echo "Error: Only PDF files less than 10MB are allowed.";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Assignment</title>
</head>
<body>
    <h2>Upload Assignment</h2>
    <form action="" method="POST" enctype="multipart/form-data">
        <label for="moduleID">Select Module:</label>
        <select name="moduleID" required>
            <option value="">Select Module</option>
            <?php
            $conn = connectDB();
            $modules = getModules($conn);
            foreach ($modules as $module) {
                echo "<option value='" . $module['ModuleID'] . "'>" . $module['ModuleName'] . "</option>";
            }
            $conn->close();
            ?>
        </select><br><br>

        <label for="assignmentName">Assignment Name:</label>
        <input type="text" name="assignmentName" required><br><br>

        <label for="description">Description:</label>
        <textarea name="description" required></textarea><br><br>

        <label for="dueDate">Due Date:</label>
        <input type="datetime-local" name="dueDate" required><br><br>

        <label for="assignment">Upload Assignment (PDF only):</label>
        <input type="file" name="assignment" accept=".pdf" required><br><br>

        <input type="submit" name="submit" value="Upload">
    </form>
</body>
</html>
