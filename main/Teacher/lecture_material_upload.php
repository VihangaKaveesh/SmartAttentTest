<?php
session_start();

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

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

// Function to fetch modules
function getModules($conn) {
    $sql = "SELECT ModuleID, ModuleName FROM modules"; // Fetch modules
    $result = $conn->query($sql);

    $modules = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $modules[] = $row;
        }
    }
    return $modules;
}

// Function to fetch materials uploaded by the logged-in teacher
function getTeacherMaterials($conn, $teacherID) {
    $sql = "SELECT lm.MaterialID, lm.ModuleID, lm.MaterialName, lm.filename, lm.folder_path, lm.UploadDate, m.ModuleName 
            FROM lecturematerials lm
            JOIN modules m ON lm.ModuleID = m.ModuleID
            WHERE lm.TeacherID = ?"; // Fetch materials by teacher ID

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $teacherID);
    $stmt->execute();
    $result = $stmt->get_result();

    $materials = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $materials[] = $row;
        }
    }
    return $materials;
}

// Check if file is uploaded
if (isset($_POST['submit'])) {
    $conn = connectDB(); // Call the connectDB() function to establish the connection

    $targetDir = "lecture_materials/";
    $targetFile = $targetDir . basename($_FILES["pdfFile"]["name"]);
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Check if file is a PDF and less than 10MB
    if ($fileType != "pdf" || $_FILES["pdfFile"]["size"] > 10000000) {
        echo "Error: Only PDF files less than 10MB are allowed to upload.";
    } else {
        // Move uploaded file to uploads folder
        if (move_uploaded_file($_FILES["pdfFile"]["tmp_name"], $targetFile)) {
            // Insert file information into database
            $filename = $_FILES["pdfFile"]["name"];
            $folder_path = $targetDir;
            $uploadDate = date('Y-m-d H:i:s', strtotime('+5 hours 30 minutes')); // Sri Lanka Time (UTC+5:30)

            // Get selected ModuleID and other details
            $moduleID = intval($_POST['module']); // Sanitize input
            $materialName = $conn->real_escape_string($_POST['materialName']); // Sanitize input
            $teacherID = $_SESSION['teacher_id']; // Get the teacher ID from session

            // Insert query into lecturematerials table
            $sql = "INSERT INTO lecturematerials (ModuleID, MaterialName, filename, folder_path, UploadDate, TeacherID)
                    VALUES ('$moduleID', '$materialName', '$filename', '$folder_path', '$uploadDate', '$teacherID')";

            if ($conn->query($sql) === TRUE) {
                echo "File uploaded and material created successfully.";
            } else {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }

            $conn->close(); // Close the connection after the query
        } else {
            echo "Error uploading file.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>PDF Upload and View Materials</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts for the Orbitron font -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <!--Bootstrap CSS-->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
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
        <a href="teacher_profile.php">Profile</a>
        <a href="Teacher-qr-generator.php">QR Code</a>
        <a href="Assignments-upload.php">Upload Assignments</a>
        <a href="sessionAnalysis.php">Session Analysis</a>
        <a href="lecture_material_upload.php">Lecture Materials</a>
        <a href="../login/login.html">Logout</a>
    </div>
</div>

    <div class="container">
        <h4 class="text-center mt-5">Upload PDF File for Lecture Material</h4>

        <!-- Upload Form -->
        <div class="card my-3">
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="module">Select Module:</label>
                        <select name="module" class="form-control" id="module" required>
                            <option value="">Select a module</option>
                            <?php
                            $conn = connectDB(); // Call the connectDB() function
                            $modules = getModules($conn); // Get the list of modules
                            foreach ($modules as $module) {
                                // Retain the selected option after form submission
                                $selected = (isset($_POST['module']) && $_POST['module'] == $module['ModuleID']) ? "selected" : "";
                                echo "<option value='" . $module['ModuleID'] . "' $selected>" . $module['ModuleName'] . "</option>";
                            }
                            $conn->close(); // Close the connection
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="materialName">Material Name:</label>
                        <input type="text" name="materialName" class="form-control" id="materialName" value="<?php echo isset($_POST['materialName']) ? htmlspecialchars($_POST['materialName']) : ''; ?>" required>
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

        <!-- View Uploaded Materials -->
        <h4 class="text-center mt-5">Your Uploaded Lecture Materials</h4>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Material Name</th>
                    <th>Module</th>
                    <th>Upload Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $conn = connectDB();
                $teacherID = $_SESSION['teacher_id']; // Fetch the teacher ID from session
                $materials = getTeacherMaterials($conn, $teacherID); // Fetch materials uploaded by this teacher

                foreach ($materials as $material) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($material['MaterialName']) . "</td>";
                    echo "<td>" . htmlspecialchars($material['ModuleName']) . "</td>"; // Display ModuleName instead of ModuleID
                    echo "<td>" . htmlspecialchars($material['UploadDate']) . "</td>";
                    echo "<td><a href='" . htmlspecialchars($material['folder_path'] . $material['filename']) . "' download>Download</a></td>";
                    echo "</tr>";
                }

                $conn->close();
                ?>
            </tbody>
        </table>
    </div>

    <!--Bootstrap JS-->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

    <script>
    document.querySelector('.hamburger').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('active');
    });
</script>
</body>
</html>
