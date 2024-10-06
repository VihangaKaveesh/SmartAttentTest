<?php
session_start();

// Check if the user is logged in as management
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'management') {
    // If not logged in or not management, redirect to the login page
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
</head>
<body>
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
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
</body>
</html>
