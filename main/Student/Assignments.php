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

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

$conn = connectDB();

$studentId = $_SESSION['student_id'];
$moduleIdQuery = "SELECT ModuleID FROM students WHERE StudentID = ?";
$stmt = $conn->prepare($moduleIdQuery);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$stmt->bind_result($moduleId);
$stmt->fetch();
$stmt->close();

// Query to select assignments for the logged-in student's module
$sql = "SELECT a.AssignmentID, a.AssignmentName, a.filename, a.DueDate, a.HandoutDate, s.filename AS submitted_file
        FROM assignments a
        LEFT JOIN submissions s ON a.AssignmentID = s.AssignmentID AND s.StudentID = ?
        WHERE a.ModuleID = ?"; // Added filter for ModuleID

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $studentId, $moduleId); // Bind both studentId and moduleId
$stmt->execute();
$result = $stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Download Assignments</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1 class="mt-4">Download Assignments</h1>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>Assignment Name</th>
                        <th>Handout Date</th>
                        <th>Due Date</th>
                        <th>Download</th>
                        <th>Submit / Update</th>
                        <th>Your Submission</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['AssignmentName']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['HandoutDate']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['DueDate']) . "</td>";

                            // Download link for the assignment
                            echo "<td><a href='../Teacher/uploads/" . htmlspecialchars($row['filename']) . "' download>Download</a></td>"; 

                            // Get current date and due date
                            $currentDateTime = new DateTime();
                            $dueDateTime = new DateTime($row['DueDate']);

                            if ($currentDateTime < $dueDateTime) {
                                // Check if the student has submitted a file
                                if ($row['submitted_file']) {
                                    // Show an Update button if a submission exists
                                    echo "<td>";
                                    echo "<form action='submit_assignment.php' method='POST' enctype='multipart/form-data'>";
                                    echo "<input type='hidden' name='AssignmentID' value='" . htmlspecialchars($row['AssignmentID']) . "'>";
                                    echo "<input type='file' name='submission_file' required>";
                                    echo "<input type='hidden' name='update' value='1'>"; // Add a hidden field to indicate update
                                    echo "<input type='submit' value='Update' class='btn btn-primary'>";
                                    echo "</form></td>";
                                } else {
                                    // Display submit button if no submission exists
                                    echo "<td><form action='submit_assignment.php' method='POST' enctype='multipart/form-data'>";
                                    echo "<input type='hidden' name='AssignmentID' value='" . htmlspecialchars($row['AssignmentID']) . "'>";
                                    echo "<input type='file' name='submission_file' required>";
                                    echo "<input type='submit' value='Submit' class='btn btn-primary'>";
                                    echo "</form></td>";
                                }
                            } else {
                                // Display message if the due date is passed
                                echo "<td><button class='btn btn-secondary' disabled>Submission Closed</button></td>";
                            }

                            // Check if the student has submitted a file
                            if ($row['submitted_file']) {
                                echo "<td><a href='submissions/" . htmlspecialchars($row['submitted_file']) . "' download>Download Your Submission</a></td>";
                            } else {
                                echo "<td>No submission</td>";
                            }

                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>No records found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
