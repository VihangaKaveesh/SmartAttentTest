<?php
session_start();

// Check if the user is logged in as a student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login/login.html");
    exit();
}

// Function to connect to the database
function connectDB() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "smartattendtest";

    // Create a new connection object
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check if connection is successful
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Return the connection object
    return $conn;
}

// Call connectDB function to get the connection
$conn = connectDB();

// Handle the file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assignmentID = $_POST['AssignmentID'];
    $studentID = $_SESSION['student_id']; // Assuming student_id is stored in session

    // Check for the uploaded file
    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['submission_file']['tmp_name'];
        $fileName = $_FILES['submission_file']['name'];

        // Set the folder path where files will be stored
        $folderPath = 'submissions/'; // Adjust this as needed

        // Ensure the folder exists
        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0777, true); // Create the folder if it doesn't exist
        }

        $destination = $folderPath . $fileName; // Full path to save the uploaded file

        // Move the file to the destination folder
        if (move_uploaded_file($fileTmpPath, $destination)) {
            // Insert submission record into the database
            $sql = "INSERT INTO submissions (StudentID, AssignmentID, filename, folder_path, SubmissionDate) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiss", $studentID, $assignmentID, $fileName, $folderPath);
            if ($stmt->execute()) {
                // Success message
                echo "<div style='text-align: center; margin-top: 50px;'>";
                echo "<h2>File submitted successfully.</h2>";
                echo "<p>You will be redirected to Assignments page in 5 seconds.</p>";
                echo "</div>";

                // JavaScript for redirect after 5 seconds
                echo "<script>
                        setTimeout(function() {
                            window.location.href = 'Assignments.php';
                        }, 5000);
                      </script>";
            } else {
                echo "Error submitting the file.";
            }
            $stmt->close();
        } else {
            echo "Error moving the uploaded file. Check directory permissions.";
        }
    } else {
        echo "Error uploading the file.";
    }
}
?>
