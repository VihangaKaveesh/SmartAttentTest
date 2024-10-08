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

// Create a connection
$conn = connectDB();

// Fetch student data from the database
$student_id = $_SESSION['student_id'];
$sql = "SELECT FirstName, LastName, Email, PhoneNumber, Username, ModuleID FROM students WHERE StudentID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Student not found.");
}

$student = $result->fetch_assoc();
$stmt->close();

// Fetch module name based on ModuleID
$module_name = '';
if ($student['ModuleID']) {
    $module_sql = "SELECT ModuleName FROM modules WHERE ModuleID = ?";
    $module_stmt = $conn->prepare($module_sql);
    $module_stmt->bind_param("i", $student['ModuleID']);
    $module_stmt->execute();
    $module_result = $module_stmt->get_result();

    if ($module_result->num_rows > 0) {
        $module = $module_result->fetch_assoc();
        $module_name = $module['ModuleName'];
    }
    $module_stmt->close();
}

$conn->close();

// Combine first name and last name
$full_name = htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
     body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
            text-align: center;
        }
        .profile {
            background-color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            display: inline-block;
        }
        h1 {
            color: #007bff;
        }
        p {
            font-size: 18px;
            margin: 10px 0;
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
        <a href="../login/login.php">Logout</a>
    </div>
</div>

<h1>Student Profile</h1>

<div class="profile">
    <p><strong>Name:</strong> <?php echo $full_name; ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($student['Email']); ?></p>
    <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($student['PhoneNumber']); ?></p>
    <p><strong>Username:</strong> <?php echo htmlspecialchars($student['Username']); ?></p>
    <p><strong>Module:</strong> <?php echo htmlspecialchars($module_name); ?></p>
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
