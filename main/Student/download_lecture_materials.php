<?php
session_start();

// Check if the user is logged in as a student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login/login.html");
    exit();
}

// Function to connect to the database
function connectDB() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "smartattendtest";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

// Retrieve the logged-in student's details (StudentID and ModuleID)
$conn = connectDB();
$studentID = $_SESSION['student_id']; // Assuming StudentID is stored in the session

$sql = "SELECT ModuleID FROM students WHERE StudentID = '$studentID'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $moduleID = $row['ModuleID']; // Fetch the ModuleID for the logged-in student
} else {
    echo "No module found for the logged-in student.";
    exit();
}

// Fetch lecture materials related to the student's module
$sqlMaterials = "SELECT MaterialName, filename, folder_path, UploadDate 
                 FROM lecturematerials 
                 WHERE ModuleID = '$moduleID'";
$resultMaterials = $conn->query($sqlMaterials);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Lecture Materials</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--Bootstrap CSS-->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h2 class="mt-4">Lecture Materials for Your Module</h2>
        <?php
        if ($resultMaterials->num_rows > 0) {
            echo "<table class='table table-bordered'>";
            echo "<thead><tr><th>Material Name</th><th>Uploaded On</th><th>Download</th></tr></thead>";
            echo "<tbody>";

            // Loop through each material and display it
            while ($rowMaterial = $resultMaterials->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($rowMaterial['MaterialName']) . "</td>";
                echo "<td>" . date('d-m-Y H:i:s', strtotime($rowMaterial['UploadDate'])) . "</td>";

                // Updated download method
                echo "<td><a href='../Teacher/lecture_materials/" . htmlspecialchars($rowMaterial['filename']) . "' download class='btn btn-success'>Download</a></td>";

                echo "</tr>";
            }

            echo "</tbody></table>";
        } else {
            echo "<p>No lecture materials are available for your module.</p>";
        }

        $conn->close(); // Close the database connection
        ?>
    </div>

    <!--Bootstrap JS-->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
</body>
</html>
