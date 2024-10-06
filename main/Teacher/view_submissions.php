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

// Function to fetch submissions for a specific assignment
function getSubmissions($conn, $assignmentID) {
    $sql = "SELECT s.SubmissionID, s.StudentID, s.SubmissionDate, s.filename, s.folder_path, st.FirstName, st.LastName, s.marks
            FROM submissions s
            JOIN students st ON s.StudentID = st.StudentID
            WHERE s.AssignmentID = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $assignmentID);
    $stmt->execute();
    $result = $stmt->get_result();

    $submissions = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $submissions[] = $row;
        }
    }
    return $submissions;
}

// Function to update marks
function updateMarks($conn, $submissionID, $marks) {
    $sql = "UPDATE submissions SET marks = ? WHERE SubmissionID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $marks, $submissionID);
    return $stmt->execute();
}

// Handle marks submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submissionID'], $_POST['marks'])) {
    $submissionID = intval($_POST['submissionID']);
    $marks = intval($_POST['marks']);

    $conn = connectDB();
    updateMarks($conn, $submissionID, $marks);
    $conn->close();
}

// Get the assignment ID from the URL
if (isset($_GET['assignmentID'])) {
    $assignmentID = intval($_GET['assignmentID']);

    $conn = connectDB();
    $submissions = getSubmissions($conn, $assignmentID);
    $conn->close();
} else {
    echo "No assignment selected.";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Submissions</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--Bootstrap CSS-->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <div class="card mt-5">
            <div class="card-header">
                <h4 class="card-title text-center">Submissions for Assignment ID: <?php echo htmlspecialchars($assignmentID); ?></h4>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Submission Date</th>
                            <th>Download</th>
                            <th>Marks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (!empty($submissions)) {
                            foreach ($submissions as $submission) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($submission['FirstName']) . " " . htmlspecialchars($submission['LastName']) . "</td>";
                                echo "<td>" . htmlspecialchars($submission['SubmissionDate']) . "</td>";
                                echo "<td><a href='../Student/submissions/" . htmlspecialchars($submission['filename']) . "' download class='btn btn-success'>Download</a></td>";

                                // Form for entering marks
                                echo "<td>
                                        <form method='POST' action=''>
                                            <input type='hidden' name='submissionID' value='" . htmlspecialchars($submission['SubmissionID']) . "'>
                                            <input type='number' name='marks' value='" . htmlspecialchars($submission['marks']) . "' min='0' class='form-control' style='width: 80px; display: inline;'>
                                            <button type='submit' class='btn btn-primary'>Save</button>
                                        </form>
                                      </td>";

                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center'>No submissions found for this assignment.</td></tr>";
                        }
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
