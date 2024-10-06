<?php
session_start();

// Check if the user is logged in as a teacher
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login/login.html");
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
    <!--Bootstrap CSS-->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>
<body>
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
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center'>No assignments found.</td></tr>";
                        }
                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!--Bootstrap JS-->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
</body>
</html>
