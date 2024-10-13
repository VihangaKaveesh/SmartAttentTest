<?php
session_start();

// Check if the user is logged in as a student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login/login.php");
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* General Styles */
body {
    font-family: 'Arial', sans-serif;
    background-color: #f8f9fa; /* Light background */
    color: #333; /* Dark text color */
    margin: 0;
    padding: 0;
}

.container {
    max-width: 960px;
    margin: 20px auto;
    padding: 20px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Heading */
h2 {
    color: #007bff; /* Bootstrap primary blue */
    font-size: 1.75rem;
    margin-bottom: 20px;
    text-align: center;
}

/* Table Styles */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

table thead th {
    background-color: #007bff; /* Blue header */
    color: #fff;
    padding: 10px;
    text-align: left;
    font-weight: bold;
}

table tbody td {
    padding: 10px;
    border: 1px solid #dee2e6;
    background-color: #f8f9fa; /* Light grey for rows */
}

table tbody tr:nth-child(even) {
    background-color: #e9ecef; /* Alternate row color */
}

table tbody tr:hover {
    background-color: #d6e9f9; /* Light blue on hover */
}

/* Button Styles */
.btn {
    padding: 6px 12px;
    font-size: 0.875rem;
    color: #fff;
    background-color: #28a745; /* Success green */
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
}

.btn:hover {
    background-color: #218838; /* Darker green on hover */
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        padding: 15px;
    }

    h2 {
        font-size: 1.5rem;
    }

    table {
        font-size: 0.9rem;
    }

    .btn {
        font-size: 0.8rem;
    }
}
     /* Hamburger Menu Icon */
     .hamburger {
            font-size: 2rem;
            cursor: pointer;
            margin: 10px;
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 2000;
        }

        /* Sidebar Styling */
        .sidebar {
            position: fixed;
            top: 0;
            left: -100%;
            height: 100%;
            width: 100vw;
            background-color:#007bff;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            transition: left 0.4s ease;
            z-index: 1500;
        }

        .sidebar.active {
            left: 0;
        }

        .nav-links a {
            color: white;
            padding: 20px;
            margin: 10px 0;
            text-decoration: none;
            font-weight: 500;
            font-size: 1.5rem;
            font-family: 'Poppins', sans-serif;
            text-align: center;
            width: 100%;
            transition: background 0.3s, padding 0.3s, transform 0.3s ease;
            position: relative;
        }

        /* Modern Hover Animation */
        .nav-links a::before {
            content: '';
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            height: 3px;
            background: #fff;
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.3s ease;
        }

        .nav-links a:hover::before {
            transform: scaleX(1);
            transform-origin: left;
        }

        .nav-links a:hover {
            background-color: #369ee4;
            border-radius: 5px;
            transform: translateY(-5px);
        }
</style>
</head>
<body>

<!-- Hamburger Icon -->
<div class="hamburger">
    <i class="fas fa-bars"></i>
</div>

<!-- Sidebar Menu -->
<div class="sidebar">
    <div class="nav-links">
        <a href="student_profile.php">Profile</a><br><br><br><br><br>
        <a href="qr-scanner.html">QR Scanner</a><br><br><br><br><br>
        <a href="Assignments.php">Assignments</a><br><br><br><br><br>
        <a href="download_lecture_materials.php">Lecture Materials</a><br><br><br><br><br>
        <a href="notice_board.php">Notice Board</a><br><br><br><br><br>
        <a href="studentAnalysis.php">Attendance Analysis</a><br><br><br><br><br>
        <a href="../login/login.php">Logout</a>
    </div>
</div>

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

    <script>
    // Toggle Sidebar
    const hamburger = document.querySelector('.hamburger');
    const sidebar = document.querySelector('.sidebar');

    hamburger.addEventListener('click', function() {
        sidebar.classList.toggle('active');
    });
</script>

</body>
</html>
