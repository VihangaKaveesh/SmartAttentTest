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
        <a href="teacher_profile.php">Profile</a><br><br><br><br><br>
        <a href="Teacher-qr-generator.php">QR Code</a><br><br><br><br><br>
        <a href="Assignments-upload.php">Upload Assignments</a><br><br><br><br><br>
        <a href="sessionAnalysis.php">Session Analysis</a><br><br><br><br><br>
        <a href="lecture_material_upload.php">Lecture Materials</a><br><br><br><br><br>
        <a href="../login/login.html">Logout</a>
    </div>
</div>
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
    <script>
    document.querySelector('.hamburger').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('active');
    });
</script>
</body>

</html>
