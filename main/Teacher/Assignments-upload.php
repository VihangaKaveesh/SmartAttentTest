<?php
session_start();

// Check if the user is logged in as a teacher
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login/login.php");
    exit();
}

// Get teacher's ID from session (assuming it's stored in the session)
$teacherID = $_SESSION['teacher_id']; // Make sure this session variable is set during login

// Initialize an empty variable to store error messages
$errorMessage = "";

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
        $errorMessage = "Error: Only PDF files less than 10MB are allowed to upload.";
    } else {
        if (move_uploaded_file($_FILES["pdfFile"]["tmp_name"], $targetFile)) {
            $filename = $_FILES["pdfFile"]["name"];
            $folder_path = $targetDir;
            $handOutDate = date('Y-m-d H:i:s', strtotime('+5 hours 30 minutes')); // Sri Lanka Time (UTC+5:30)

            // Get selected ModuleID and other details
            $moduleID = intval($_POST['module']);
            $assignmentName = $conn->real_escape_string($_POST['assignmentName']);
            $dueDate = $conn->real_escape_string($_POST['dueDate']);

            // Check if Due Date is before HandOut Date
            if (strtotime($dueDate) < strtotime($handOutDate)) {
                $errorMessage = "Error: Due date cannot be before the handout date.";
            } else {
                // Insert query including TeacherID
                $sql = "INSERT INTO assignments (ModuleID, AssignmentName, filename, folder_path, HandOutDate, DueDate, TeacherID)
                        VALUES ('$moduleID', '$assignmentName', '$filename', '$folder_path', '$handOutDate', '$dueDate', '$teacherID')";

                if ($conn->query($sql) === TRUE) {
                    $successMessage = "File uploaded and assignment created successfully.";
                } else {
                    $errorMessage = "Error: " . $sql . "<br>" . $conn->error;
                }
            }
        } else {
            $errorMessage = "Error uploading file.";
        }
    }
    $conn->close();
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
<<<<<<< HEAD
        /* Global Styles */
=======
                /* Global Styles */
>>>>>>> fcde0791ec127f0f790c736b3e565ab943447e64
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            font-family: 'Poppins', sans-serif;
        }

        /* Container styling */
        .container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        /* Card styling */
        .card {
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-top: 20px;
        }

        /* Form styling */
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
            margin: 5px;
        }

        button:hover {
            background-color: #388E3C;
        }

        h4 {
            text-align: center;
            margin-bottom: 20px;
        }

        /* Table styling */
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

<<<<<<< HEAD
/* Center message containers */
.message-container {
    display: flex;
    justify-content: center; /* Center horizontally */
    align-items: center; /* Center vertically */
    margin: 20px 0; /* Add margin for spacing */
}

/* Error and success message styling */
.error-message, .success-message {
    padding: 10px;
    border-radius: 5px;
    width: 50%; /* Adjust width as necessary */
    text-align: center; /* Center text inside the message box */
}

.error-message {
    color: red;
    border: 1px solid red;
    background-color: #ffe6e6;
}

.success-message {
    color: green;
    border: 1px solid green;
    background-color: #e6ffe6;
}

    </style>
=======
        .nav-links a:hover {
            background-color: #388E3C;
            border-radius: 5px;
            transform: translateY(-5px);
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
        }
</style>
>>>>>>> fcde0791ec127f0f790c736b3e565ab943447e64
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
        <a href="../login/login.php">Logout</a>
    </div>
</div>

<!-- Display error message if it exists -->
<?php if (!empty($errorMessage)): ?>
    <div class="message-container">
        <div class="error-message"><?= $errorMessage ?></div>
    </div>
<?php endif; ?>

<!-- Display success message if it exists -->
<?php if (!empty($successMessage)): ?>
    <div class="message-container">
        <div class="success-message"><?= $successMessage ?></div>
    </div>
<?php endif; ?>

    
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