<?php
session_start();

// Check if the user is logged in as a student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login/login.html");
    exit();
}

// Connect to the database
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

// Fetch assignments for the logged-in student based on their module ID
function getAssignmentsForStudent($conn, $studentID) {
    $sql = "SELECT a.AssignmentID, a.AssignmentName, a.Description, a.DueDate, a.Assignment, m.ModuleName
            FROM assignments a
            JOIN modules m ON a.ModuleID = m.ModuleID
            JOIN students s ON a.ModuleID = s.ModuleID
            WHERE s.StudentID = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $studentID);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $assignments = [];
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    return $assignments;
}

// Check if the form for submitting assignment is submitted
if (isset($_POST['submit_assignment'])) {
    $conn = connectDB();
    
    $assignmentID = $_POST['assignmentID'];
    $studentID = $_SESSION['studentID']; // Assuming student ID is stored in session
    $submissionDate = date('Y-m-d H:i:s'); // Current date and time for submission

    // Handle file upload for the submitted assignment
    $targetDir = "uploads/";
    $fileName = basename($_FILES["submissionFile"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

    // Allow certain file formats for submission
    $allowedTypes = array("pdf", "doc", "docx", "zip");

    if (in_array($fileType, $allowedTypes)) {
        // Upload the file to the server
        if (move_uploaded_file($_FILES["submissionFile"]["tmp_name"], $targetFilePath)) {
            // Insert submission data into the database
            $sql = "INSERT INTO submissions (StudentID, AssignmentID, SubmissionFile, SubmissionDate)
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiss", $studentID, $assignmentID, $targetFilePath, $submissionDate);

            if ($stmt->execute()) {
                echo "Assignment submitted successfully.";
            } else {
                echo "Error: " . $stmt->error;
            }
        } else {
            echo "Sorry, there was an error uploading your submission file.";
        }
    } else {
        echo "Sorry, only PDF, DOC, DOCX, & ZIP files are allowed for submission.";
    }

    $conn->close();
}

$conn = connectDB();
$studentID = $_SESSION['studentID']; // Get student ID from session
$assignments = getAssignmentsForStudent($conn, $studentID);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments</title>
</head>
<body>
    <h2>Your Assignments</h2>
    <table border="1">
        <tr>
            <th>Assignment Name</th>
            <th>Description</th>
            <th>Due Date</th>
            <th>Download</th>
            <th>Submit</th>
        </tr>
        <?php foreach ($assignments as $assignment): ?>
            <tr>
                <td><?php echo $assignment['AssignmentName']; ?></td>
                <td><?php echo $assignment['Description']; ?></td>
                <td><?php echo $assignment['DueDate']; ?></td>
                <td>
                    <a href="<?php echo $assignment['Assignment']; ?>" download>Download</a>
                </td>
                <td>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="assignmentID" value="<?php echo $assignment['AssignmentID']; ?>">
                        <input type="file" name="submissionFile" required>
                        <input type="submit" name="submit_assignment" value="Submit">
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
