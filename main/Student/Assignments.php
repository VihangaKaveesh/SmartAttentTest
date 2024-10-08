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

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

$conn = connectDB();

$studentId = $_SESSION['student_id'];
$moduleIdQuery = "SELECT ModuleID FROM students WHERE StudentID = ?";
$stmt = $conn->prepare($moduleIdQuery);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$stmt->bind_result($moduleId);
$stmt->fetch();
$stmt->close();

// Fetch attendance percentage
function getAttendancePercentage($conn, $studentId) {
    // Query to get the attendance details for the student
    $query = "SELECT Status FROM Attendance WHERE StudentID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();

    $totalClasses = $result->num_rows;
    $presentCount = 0;

    while ($row = $result->fetch_assoc()) {
        if ($row['Status'] === 'present') {
            $presentCount++;
        }
    }

    return ($totalClasses > 0) ? ($presentCount / $totalClasses) * 100 : 0;
}

$attendancePercentage = getAttendancePercentage($conn, $studentId);
if ($attendancePercentage < 80) {
    echo "<script>alert('You must have an attendance percentage of at least 80% to send an email.');</script>";
    exit();
}

// Query to select assignments for the logged-in student's module
$sql = "SELECT a.AssignmentID, a.AssignmentName, a.filename, a.DueDate, a.HandoutDate, 
               s.filename AS submitted_file, s.marks
        FROM assignments a
        LEFT JOIN submissions s ON a.AssignmentID = s.AssignmentID AND s.StudentID = ?
        WHERE a.ModuleID = ?"; // Added filter for ModuleID

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $studentId, $moduleId); // Bind both studentId and moduleId
$stmt->execute();
$result = $stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Download Assignments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* General page styles */
body {
    font-family: Arial, sans-serif;
    background-color: #f8f9fa; /* Light gray background */
    margin: 0;
    padding: 20px;
}

h1 {
    font-size: 28px;
    text-align: center;
    margin-bottom: 20px;
    color: #333;
}

/* Container styles */
.container {
    max-width: 1000px;
    margin: 0 auto;
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

/* Table styles */
.table-responsive {
    margin-top: 20px;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th, 
.table td {
    padding: 12px;
    text-align: center;
}

.table thead th {
    background-color: #007bff;
    color: #fff;
}

.table tbody tr:nth-child(even) {
    background-color: #f2f2f2;
}

.table tbody tr:hover {
    background-color: #e9ecef;
}

/* Button styles */
.btn {
    padding: 8px 16px;
    font-size: 14px;
    cursor: pointer;
    border-radius: 4px;
    border: none;
}

.btn-primary {
    background-color: #007bff;
    color: white;
    border: 1px solid #007bff;
}

.btn-primary:hover {
    background-color: #0056b3;
    border-color: #0056b3;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
    border: 1px solid #6c757d;
}

.btn-secondary:hover {
    background-color: #5a6268;
    border-color: #545b62;
}

.btn[disabled] {
    background-color: #d6d8db;
    color: #6c757d;
    border: none;
}

/* Form styles */
form {
    display: inline-block;
    margin: 0;
}

input[type="file"] {
    margin-bottom: 10px;
}

input[type="submit"] {
    margin-top: 10px;
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
        <a href="../login/login.html">Logout</a>
    </div>
</div>

    <div class="container">
        <h1 class="mt-4">Download Assignments</h1>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>Assignment Name</th>
                        <th>Handout Date</th>
                        <th>Due Date</th>
                        <th>Download</th>
                        <th>Submit / Update</th>
                        <th>Your Submission</th>
                        <th>Marks</th> <!-- New column for Marks -->
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['AssignmentName']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['HandoutDate']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['DueDate']) . "</td>";

                            // Download link for the assignment
                            echo "<td><a href='../Teacher/uploads/" . htmlspecialchars($row['filename']) . "' download>Download</a></td>"; 

                            // Get current date and due date
                            $currentDateTime = new DateTime();
                            $dueDateTime = new DateTime($row['DueDate']);

                            if ($currentDateTime < $dueDateTime) {
                                // Check if the student has submitted a file
                                if ($row['submitted_file']) {
                                    // Show an Update button if a submission exists
                                    echo "<td>";
                                    echo "<form action='submit_assignment.php' method='POST' enctype='multipart/form-data'>";
                                    echo "<input type='hidden' name='AssignmentID' value='" . htmlspecialchars($row['AssignmentID']) . "'>";
                                    echo "<input type='file' name='submission_file' required>";
                                    echo "<input type='hidden' name='update' value='1'>"; // Add a hidden field to indicate update
                                    echo "<input type='submit' value='Update' class='btn btn-primary'>";
                                    echo "</form></td>";
                                } else {
                                    // Display submit button if no submission exists
                                    echo "<td><form action='submit_assignment.php' method='POST' enctype='multipart/form-data'>";
                                    echo "<input type='hidden' name='AssignmentID' value='" . htmlspecialchars($row['AssignmentID']) . "'>";
                                    echo "<input type='file' name='submission_file' required>";
                                    echo "<input type='submit' value='Submit' class='btn btn-primary'>";
                                    echo "</form></td>";
                                }
                            } else {
                                // Display message if the due date is passed
                                echo "<td><button class='btn btn-secondary' disabled>Submission Closed</button></td>";
                            }

                            // Check if the student has submitted a file
                            if ($row['submitted_file']) {
                                echo "<td><a href='submissions/" . htmlspecialchars($row['submitted_file']) . "' download>Download Your Submission</a></td>";
                            } else {
                                echo "<td>No submission</td>";
                            }

                            // Display marks; show "Pending" if marks are NULL or empty
                            $marksDisplay = (!empty($row['marks'])) ? htmlspecialchars($row['marks']) : "Pending";
                            echo "<td>" . $marksDisplay . "</td>";
                            
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7'>No records found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
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
