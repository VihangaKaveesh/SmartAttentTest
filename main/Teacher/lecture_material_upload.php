<?php
session_start();

// Check if the user is logged in as a teacher
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
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

// Variable to store error message
$errorMessage = '';
$successMessage = ''; // Add a success message variable

// Check if file is uploaded
if (isset($_POST['submit'])) {
    $conn = connectDB(); // Call the connectDB() function to establish the connection

    $targetDir = "lecture_materials/";
    $targetFile = $targetDir . basename($_FILES["pdfFile"]["name"]);
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Check if file is a PDF and less than 10MB
    if ($fileType != "pdf" || $_FILES["pdfFile"]["size"] > 10000000) {
        $errorMessage = "Error: Only PDF files less than 10MB are allowed to upload.";
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
                $successMessage = "File uploaded and material created successfully."; // Set success message
                echo "<script>window.onload = function() { document.getElementById('uploadForm').reset(); }</script>"; // Reset the form after upload
            } else {
                $errorMessage = "Error: " . $sql . "<br>" . $conn->error;
            }

            $conn->close(); // Close the connection after the query
        } else {
            $errorMessage = "Error uploading file.";
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
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        /* Container styling */
        .container {
            max-width: 900px;
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
            height: 100%;
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
        <a href="teacher_profile.php">Profile</a><br><br><br><br><br>
        <a href="Teacher-qr-generator.php">QR Code</a><br><br><br><br><br>
        <a href="Assignments-upload.php">Upload Assignments</a><br><br><br><br><br>
        <a href="sessionAnalysis.php">Session Analysis</a><br><br><br><br><br>
        <a href="lecture_material_upload.php">Lecture Materials</a><br><br><br><br><br>
        <a href="../login/login.php">Logout</a>
    </div>
</div>

<div class="container">
    <h4 class="text-center mt-5">Upload Lecture Material</h4>

    <!-- Display error message if exists -->
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger text-center">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
    <?php endif; ?>

    <!-- Display success message if exists -->
    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success text-center">
            <?php echo htmlspecialchars($successMessage); ?>
        </div>
    <?php endif; ?>

<!-- Upload Form -->
<div class="card my-3">
    <div class="card-body">
        <form id="uploadForm" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="course">Select Module:</label>
                <select id="module" name="module" class="form-control">
                    <?php
                    $conn = connectDB(); // Call the connectDB() function

                    // Check if the session variable for TeacherID is set correctly
                    if (isset($_SESSION['teacher_id'])) {
                        $teacher_id = $_SESSION['teacher_id']; // Use the correct session variable
                    } else {
                        echo "<option value=''>No Teacher ID found</option>"; // Add this line to handle missing session
                        exit(); // Exit if no TeacherID is found
                    }

                    // Fetch modules specific to the logged-in teacher
                    $query = "SELECT ModuleID, ModuleName FROM modules WHERE TeacherID = ?";
                    $stmt = $conn->prepare($query);
                    if ($stmt) {
                        $stmt->bind_param("i", $teacher_id);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        // Populate the dropdown with module names for the specific teacher
                        if ($result->num_rows > 0) {
                            while ($module = $result->fetch_assoc()) {
                                $selected = (isset($currentModule) && $currentModule == $module['ModuleID']) ? "selected" : "";
                                echo "<option value='" . htmlspecialchars($module['ModuleID']) . "' $selected>" . htmlspecialchars($module['ModuleName']) . "</option>";
                            }
                        } else {
                            echo "<option value=''>No modules available</option>"; // Message when no modules are found
                        }
                    } else {
                        echo "<option value=''>Error fetching modules</option>"; // Message if the statement failed
                    }

                    $stmt->close();
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
                <button type="submit" name="submit">Upload</button>
            </form>
        </div>
    </div>


    <h4 class="text-center mt-5">Uploaded Materials</h4>
        <table>
            <thead>
                <tr>
                    <th>Material Name</th>
                    <th>Module Name</th>
                    <th>Uploaded Date</th>
                    <th>Download</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch materials uploaded by the teacher
                $teacherID = $_SESSION['teacher_id'];
                $conn = connectDB();
                $materials = getTeacherMaterials($conn, $teacherID);
                foreach ($materials as $material) {
                    echo "<tr>
                            <td>" . htmlspecialchars($material['MaterialName']) . "</td>
                            <td>" . htmlspecialchars($material['ModuleName']) . "</td>
                            <td>" . htmlspecialchars($material['UploadDate']) . "</td>
                            <td><a href='" . htmlspecialchars($material['folder_path']) . htmlspecialchars($material['filename']) . "' target='_blank'>Download</a></td>
                          </tr>";
                }
                $conn->close();
                ?>
            </tbody>
        </table>
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
