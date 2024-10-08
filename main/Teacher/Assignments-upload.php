<?php
session_start();

// Check if the user is logged in as a teacher
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login/login.php");
    exit();
}

// Get teacher's ID from session (assuming it's stored in the session)
$teacherID = $_SESSION['teacher_id']; // Make sure this session variable is set during login

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

// Function to fetch modules taught by the logged-in teacher
function getModules($conn, $teacherID) {
    $sql = "SELECT ModuleID, ModuleName 
            FROM modules 
            WHERE TeacherID = ?"; // Only fetch modules assigned to the teacher

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $teacherID);
    $stmt->execute();
    $result = $stmt->get_result();

    $modules = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $modules[] = $row;
        }
    }
    return $modules;
}

// Function to fetch assignments uploaded by the logged-in teacher
function getTeacherAssignments($conn, $teacherID) {
    $sql = "SELECT a.AssignmentID, a.ModuleID, a.AssignmentName, a.filename, a.folder_path, a.HandOutDate, a.DueDate, m.ModuleName 
            FROM assignments a
            JOIN modules m ON a.ModuleID = m.ModuleID
            WHERE a.TeacherID = ?"; // Fetch assignments by teacher ID

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $teacherID);
    $stmt->execute();
    $result = $stmt->get_result();

    $assignments = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $assignments[] = $row;
        }
    }
    return $assignments;
}

// Check if file is uploaded
if (isset($_POST['submit'])) {
    $conn = connectDB();

    $targetDir = "uploads/";
    $targetFile = $targetDir . basename($_FILES["pdfFile"]["name"]);
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Check if file is a PDF and less than 10MB
    if ($fileType != "pdf" || $_FILES["pdfFile"]["size"] > 10000000) {
        echo "Error: Only PDF files less than 10MB are allowed to upload.";
    } else {
        if (move_uploaded_file($_FILES["pdfFile"]["tmp_name"], $targetFile)) {
            $filename = $_FILES["pdfFile"]["name"];
            $folder_path = $targetDir;
            $handOutDate = date('Y-m-d H:i:s', strtotime('+5 hours 30 minutes')); // Sri Lanka Time (UTC+5:30)

            // Get selected ModuleID and other details
            $moduleID = intval($_POST['module']);
            $assignmentName = $conn->real_escape_string($_POST['assignmentName']);
            $dueDate = $conn->real_escape_string($_POST['dueDate']);

            // Insert query including TeacherID
            $sql = "INSERT INTO assignments (ModuleID, AssignmentName, filename, folder_path, HandOutDate, DueDate, TeacherID)
                    VALUES ('$moduleID', '$assignmentName', '$filename', '$folder_path', '$handOutDate', '$dueDate', '$teacherID')";

            if ($conn->query($sql) === TRUE) {
                echo "File uploaded and assignment created successfully.";
            } else {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }

            $conn->close();
        } else {
            echo "Error uploading file.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>PDF Upload Form</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
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
            background-color: #4CAF50;
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
            background-color: #388E3C;
            border-radius: 5px;
            transform: translateY(-5px);
        }

        /* General Styles */
body {
    font-family: Arial, sans-serif;
    background-color: #f8f9fa; /* Light background color */
}

.container {
    max-width: 800px; /* Maximum width of the container */
    margin: 0 auto; /* Center the container */
    padding: 20px; /* Add padding */
}

/* Card Styles */
.card {
    border: 1px solid #dee2e6; /* Border color */
    border-radius: 0.25rem; /* Rounded corners */
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Subtle shadow */
    margin-bottom: 30px; /* Spacing between cards */
}

.card-header {
    background-color: #4CAF50; /* Bootstrap primary color */
    color: white; /* White text */
    padding: 15px; /* Padding in header */
    text-align: center; /* Center align text */
}

.card-title {
    margin: 0; /* Remove margin */
}

/* Form Styles */
.form-group {
    margin-bottom: 15px; /* Space between form groups */
}

label {
    font-weight: bold; /* Bold labels */
}

/* Button Styles */
.btn {
    margin-top: 10px; /* Space above buttons */
}

.btn-block {
    width: 100%; /* Full width buttons */
}

/* Table Styles */
.table {
    width: 100%; /* Full width table */
    border-collapse: collapse; /* Collapse borders */
}

.table th, .table td {
    padding: 12px; /* Padding in table cells */
    text-align: left; /* Align text to the left */
}

.table th {
    background-color: #4CAF50; /* Header background color */
    color: white; /* Header text color */
}

.table-bordered th, .table-bordered td {
    border: 1px solid #dee2e6; /* Border color */
}

.table-striped tbody tr:nth-of-type(odd) {
    background-color: #f2f2f2; /* Light grey background for odd rows */
}

/* Responsive Styles */
@media (max-width: 768px) {
    .container {
        padding: 10px; /* Less padding on smaller screens */
    }
    
    .btn {
        margin-top: 5px; /* Less space above buttons */
    }
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
        <a href="../login/login.php">Logout</a>
    </div>
</div>

    <div class="container">
        <!-- Assignment Upload Form -->
        <div class="card mt-5">
            <div class="card-header">
                <h4 class="card-title text-center">Upload PDF File for Assignment</h4>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="module">Select Module:</label>
                        <select name="module" class="form-control" id="module" required>
                            <option value="">Select a module</option>
                            <?php
                            $conn = connectDB();
                            $modules = getModules($conn, $teacherID); // Pass teacher ID to filter modules
                            foreach ($modules as $module) {
                                echo "<option value='" . $module['ModuleID'] . "'>" . $module['ModuleName'] . "</option>";
                            }
                            $conn->close();
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="assignmentName">Assignment Name:</label>
                        <input type="text" name="assignmentName" class="form-control" id="assignmentName" required>
                    </div>
                    <div class="form-group">
                        <label for="dueDate">Due Date:</label>
                        <input type="datetime-local" name="dueDate" class="form-control" id="dueDate" required>
                    </div>
                    <div class="form-group">
                        <label for="pdfFile">Select PDF File:</label>
                        <input type="file" name="pdfFile" class="form-control-file" id="pdfFile" required>
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary btn-block">Upload File</button>
                    <button type="reset" class="btn btn-warning btn-block">Reset</button>
                </form>
            </div>
        </div>

<!-- Assignments Table (Assignments uploaded by the logged-in teacher) -->
<div class="card mt-5">
    <div class="card-header">
        <h4 class="card-title text-center">Your Uploaded Assignments</h4>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Assignment Name</th>
                    <th>Module</th>
                    <th>HandOut Date</th>
                    <th>Due Date</th>
                    <th>Download</th>
                    <th>View Submissions</th> <!-- New column for View Submissions -->
                </tr>
            </thead>
            <tbody>
                <?php
                $conn = connectDB();
                $assignments = getTeacherAssignments($conn, $teacherID);
                if (!empty($assignments)) {
                    foreach ($assignments as $assignment) {
                        echo "<tr>";
                        echo "<td>" . $assignment['AssignmentName'] . "</td>";
                        echo "<td>" . $assignment['ModuleName'] . "</td>";
                        echo "<td>" . $assignment['HandOutDate'] . "</td>";
                        echo "<td>" . $assignment['DueDate'] . "</td>";
                        echo "<td><a href='" . $assignment['folder_path'] . $assignment['filename'] . "' target='_blank' class='btn btn-info'>Download</a></td>";
                        
                        // New "View Submissions" button
                        echo "<td><a href='view_submissions.php?assignmentID=" . $assignment['AssignmentID'] . "' class='btn btn-secondary'>View Submissions</a></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' class='text-center'>No assignments found.</td></tr>";
                }
                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
</div>
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