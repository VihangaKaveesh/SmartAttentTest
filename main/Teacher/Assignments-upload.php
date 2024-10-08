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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload PDF File for Assignment</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts for the Orbitron font -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <style>
             body {
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            /* font-family: 'Orbitron', sans-serif; */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Center the container */
        .container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .card {
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            font-size: 16px;
            font-weight: bold;
        }

        .form-control, .form-control-file, select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            width: 100%;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
        }

        button:hover {
            background-color: #388E3C;
        }

        h4 {
            text-align: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th, table td {
            padding: 12px;
            border: 1px solid #ddd;
        }

        table th {
            background-color: #4CAF50;
            color: white;
        }

        table td a {
            color: #4CAF50;
            text-decoration: none;
        }

        table td a:hover {
            text-decoration: underline;
        }

        /* Sidebar Styling */
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
            background-color: #388E3C;
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
        <a href="teacher_profile.php">Profile</a><br><br><br><br><br>
        <a href="Teacher-qr-generator.php">QR Code</a><br><br><br><br><br>
        <a href="Assignments-upload.php">Upload Assignments</a><br><br><br><br><br>
        <a href="sessionAnalysis.php">Session Analysis</a><br><br><br><br><br>
        <a href="lecture_material_upload.php">Lecture Materials</a><br><br><br><br><br>
        <a href="../login/login.html">Logout</a>
    </div>
</div>

<!-- Main Content -->
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
                        $modules = getModules($conn, $teacherID);
                        foreach ($modules as $module) {
                            echo "<option value=\"" . $module['ModuleID'] . "\">" . $module['ModuleName'] . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="assignmentName">Assignment Name:</label>
                    <input type="text" name="assignmentName" class="form-control" id="assignmentName" required>
                </div>
                <div class="form-group">
                    <label for="pdfFile">Select PDF File:</label>
                    <input type="file" name="pdfFile" class="form-control-file" id="pdfFile" accept=".pdf" required>
                </div>
                <div class="form-group">
                    <label for="dueDate">Due Date:</label>
                    <input type="date" name="dueDate" class="form-control" id="dueDate" required>
                </div>
                <button type="submit" name="submit" class="btn btn-block">Upload</button>
            </form>
        </div>
    </div>

    <!-- Display Uploaded Assignments -->
    <div class="card">
        <div class="card-header">
            <h4 class="card-title text-center">Uploaded Assignments</h4>
        </div>
        <div class="card-body">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Assignment Name</th>
                        <th>Module</th>
                        <th>File</th>
                        <th>Handout Date</th>
                        <th>Due Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $conn = connectDB();
                    $assignments = getTeacherAssignments($conn, $teacherID);
                    foreach ($assignments as $assignment) {
                        echo "<tr>";
                        echo "<td>" . $assignment['AssignmentName'] . "</td>";
                        echo "<td>" . $assignment['ModuleName'] . "</td>";
                        echo "<td><a href=\"" . $assignment['folder_path'] . $assignment['filename'] . "\">" . $assignment['filename'] . "</a></td>";
                        echo "<td>" . $assignment['HandOutDate'] . "</td>";
                        echo "<td>" . $assignment['DueDate'] . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.querySelector('.hamburger').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('active');
    });
</script>
</body>
</html>
