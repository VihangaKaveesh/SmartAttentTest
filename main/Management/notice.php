<?php
session_start();

// Check if the user is logged in as management
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'management') {
    // If not logged in or not management, redirect to the login page
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

// Handle file upload
if (isset($_POST['submit'])) {
    $conn = connectDB(); // Establish database connection

    // Folder path where files will be uploaded
    $targetDir = "notices/";
    $targetFile = $targetDir . basename($_FILES["pdfFile"]["name"]);
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Check if the uploaded file is a PDF and less than 10MB
    if ($fileType != "pdf" || $_FILES["pdfFile"]["size"] > 10000000) {
        echo "Error: Only PDF files less than 10MB are allowed.";
    } else {
        // Move uploaded file to the target directory
        if (move_uploaded_file($_FILES["pdfFile"]["tmp_name"], $targetFile)) {
            $noticeName = $_POST['noticeName']; // Notice Name from the form
            $filename = $_FILES["pdfFile"]["name"];
            $folder_path = $targetDir;

            // Insert query to store file details into notice_board table (noticeID is auto-incremented)
            $sql = "INSERT INTO notice_board (noticeName, filename, folder_path)
                    VALUES (?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $noticeName, $filename, $folder_path);

            if ($stmt->execute()) {
                echo "Notice uploaded successfully.";
            } else {
                echo "Error: " . $stmt->error;
            }

            $stmt->close();
            $conn->close(); // Close the database connection
        } else {
            echo "Error uploading the file.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Notices</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <style>
       body {
    font-family: Arial, sans-serif;
    background-color: #f8f9fa;
    margin: 0;
    padding: 0;
}

/* Card and form container */
.container {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.card {
    width: 100%;
    max-width: 500px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    background-color: white;
}

.card-header {
    background-color: #a03aba; /* Match the sidebar color */
    color: white;
    padding: 15px;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
    text-align: center;
}

.card-body {
    padding: 20px;
}

.card-title {
    font-size: 1.5rem;
    font-weight: bold;
}

/* Form elements */
.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: bold;
}

.form-control {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 1rem;
}

.form-control-file {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 1rem;
    margin-top: 0.5rem;
}

/* Buttons */
.btn {
    width: 100%;
    padding: 0.75rem;
    font-size: 1rem;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.btn-primary {
    background-color: #a03aba; /* Use the primary color from sidebar */
    color: white;
}

.btn-primary:hover {
    background-color: #d448f7; /* Use the hover color */
}

.btn-warning {
    background-color: #d448f7; /* Adjust warning button color */
    color: white;
}

.btn-warning:hover {
    background-color: #a03aba; /* Reverse the hover effect */
}

/* Sidebar and navigation */
.hamburger {
    font-size: 2rem;
    cursor: pointer;
    margin: 10px;
    position: absolute;
    top: 10px;
    left: 10px;
    z-index: 2000;
}

.sidebar {
    position: fixed;
    top: 0;
    left: -100%;
    height: 100%;
    width: 100vw;
    background-color: #a03aba; /* Sidebar color */
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
    background-color: #d448f7;
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
        <a href="manageStudents.php">Students</a><br><br><br><br><br>
        <a href="addModules.php">Modules</a><br><br><br><br><br>
        <a href="manageTeachers.php">Teachers</a><br><br><br><br><br>
        <a href="notice.php">Notices</a><br><br><br><br><br>
        <a href="addLabs.php">Labs</a><br><br><br><br><br>
        <a href="../login/login.php">Logout</a>
    </div>
</div>
    <div class="container d-flex justify-content-center align-items-center" style="height:100vh">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title text-center">Upload Notices</h4>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="noticeName">Notice Name:</label>
                        <input type="text" name="noticeName" class="form-control" id="noticeName" required>
                    </div>
                    <div class="form-group">
                        <label for="pdfFile">Select PDF File:</label>
                        <input type="file" name="pdfFile" class="form-control-file" id="pdfFile" required>
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary btn-block">Upload Notice</button>
                    <button type="reset" class="btn btn-warning btn-block">Reset</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <!-- <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script> -->

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
