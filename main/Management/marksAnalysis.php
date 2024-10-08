<?php
session_start();

// Check if the user is logged in as management
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'management') {
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

// Fetch average marks for all assignments under a module
function getAverageMarks($module_id) {
    $conn = connectDB();
    $query = "
        SELECT assignments.AssignmentID, assignments.AssignmentName, AVG(assignmentmarks.MarksObtained) as averageMarks 
        FROM assignments 
        LEFT JOIN assignmentmarks ON assignments.AssignmentID = assignmentmarks.AssignmentID 
        WHERE assignments.ModuleID = ?
        GROUP BY assignments.AssignmentID";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $module_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();
    $conn->close();
    return $data;
}

// Fetch specific assignment details and student marks
function getAssignmentDetails($assignment_id) {
    $conn = connectDB();
    $query = "
        SELECT students.StudentID, CONCAT(students.FirstName, ' ', students.LastName) AS StudentName, assignmentmarks.MarksObtained
        FROM assignmentmarks
        LEFT JOIN students ON assignmentmarks.StudentID = students.StudentID
        WHERE assignmentmarks.AssignmentID = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();
    $conn->close();
    return $data;
}

// Fetch module name by module ID
function getModuleName($module_id) {
    $conn = connectDB();
    $query = "SELECT ModuleName FROM modules WHERE ModuleID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $module_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $moduleName = $result->fetch_assoc()['ModuleName'] ?? 'Unknown Module';

    $stmt->close();
    $conn->close();
    return $moduleName;
}


// Fetch all assignments under a module for the dropdown
function getAssignments($module_id) {
    $conn = connectDB();
    $query = "SELECT AssignmentID, AssignmentName FROM assignments WHERE ModuleID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $module_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $assignments = [];

    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }

    $stmt->close();
    $conn->close();
    return $assignments;
}

// Fetch the submission statistics for a specific assignment
function getSubmissionStats($assignment_id, $module_id) {
    $conn = connectDB();
    $query = "
        SELECT 
            (SELECT COUNT(*) FROM students WHERE ModuleID = ?) AS totalStudents,
            (SELECT COUNT(*) FROM assignmentmarks WHERE AssignmentID = ?) AS submittedStudents";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $module_id, $assignment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    $stmt->close();
    $conn->close();
    return $data;
}

$module_id = $_GET['module_id'] ?? null;
$assignment_id = $_GET['assignment_id'] ?? null;
$average_marks_data = [];
$assignment_details = [];
$submission_stats = [];

// Get average marks for all assignments under the selected module
if ($module_id) {
    $average_marks_data = getAverageMarks($module_id);
}

// Get specific assignment details if an assignment is selected
if ($assignment_id) {
    $assignment_details = getAssignmentDetails($assignment_id);
    $submission_stats = getSubmissionStats($assignment_id, $module_id);
}
// Get the selected module name
$module_name = $module_id ? getModuleName($module_id) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marks Analysis</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 80%;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        h1, h2 {
            text-align: center;
            color: #0056b3;
        }

        form {
            text-align: center;
            margin-bottom: 30px;
        }

        select {
            padding: 10px;
            font-size: 1em;
            margin-right: 10px;
        }

        .chart-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 40px 0;
            height: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 15px;
            text-align: center;
            border: 1px solid #dee2e6;
            font-size: 1.1em;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f1f1f1;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        @media (max-width: 768px) {
            .container {
                max-width: 100%;
            }

            canvas {
                width: 300px !important;
                height: 300px !important;
            }
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
            background-color:#a03aba;
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
            background-color: #d448f7;
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
        <a href="manageStudents.php">Students</a><br><br><br><br><br>
        <a href="addModules.php">Modules</a><br><br><br><br><br>
        <a href="manageTeachers.php">Teachers</a><br><br><br><br><br>
        <a href="notice.php">Notices</a><br><br><br><br><br>
        <a href="addLabs.php">Labs</a><br><br><br><br><br>
        <a href="../login/login.html">Logout</a>
    </div>
</div>
<div class="container">
<h1>Marks Analysis for <?php echo htmlspecialchars($module_name); ?></h1>

    <!-- Dropdown to select an assignment -->
    <form method="GET">
        <label for="assignment_id">Select an Assignment:</label>
        <select name="assignment_id" id="assignment_id" onchange="this.form.submit()">
            <option value="">-- Select Assignment --</option>
            <?php foreach (getAssignments($module_id) as $assignment): ?>
                <option value="<?php echo $assignment['AssignmentID']; ?>" <?php echo ($assignment['AssignmentID'] == $assignment_id) ? 'selected' : ''; ?>>
                    <?php echo $assignment['AssignmentName']; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="module_id" value="<?php echo $module_id; ?>">
    </form>

    <!-- Overall Module Average Marks Chart -->
    <?php if ($module_id && !$assignment_id): ?>
        <h2>Average Marks for All Assignments</h2>
        <div class="chart-container">
            <canvas id="averageMarksChart"></canvas>
        </div>

        <script>
            var ctx = document.getElementById('averageMarksChart').getContext('2d');
            var averageMarksChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($average_marks_data, 'AssignmentName')); ?>,
                    datasets: [{
                        label: 'Average Marks',
                        data: <?php echo json_encode(array_column($average_marks_data, 'averageMarks')); ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            suggestedMax: 100
                        }
                    }
                }
            });
        </script>
    <?php endif; ?>

    <!-- Detailed Student Marks for Selected Assignment -->
    <?php if ($assignment_id): ?>
        <h2>Student Marks for Assignment</h2>
        <div class="chart-container">
            <canvas id="studentMarksChart"></canvas>
        </div>

        <script>
            var ctx = document.getElementById('studentMarksChart').getContext('2d');
            var marksData = <?php echo json_encode(array_column($assignment_details, 'MarksObtained')); ?>;

            // Calculate the average marks
            var totalMarks = marksData.reduce((a, b) => a + (b || 0), 0);
            var averageMarks = totalMarks / marksData.filter(mark => mark !== null).length;

            var studentMarksChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($assignment_details, 'StudentName')); ?>,
                    datasets: [{
                        label: 'Marks Obtained',
                        data: marksData,
                        backgroundColor: 'rgba(153, 102, 255, 0.2)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Average Marks',
                        data: new Array(marksData.length).fill(averageMarks),
                        type: 'line',
                        borderColor: 'red',
                        borderWidth: 2,
                        fill: false
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            suggestedMax: 100
                        }
                    }
                }
            });
        </script>

        <!-- Submission Statistics -->
        <h2>Submission Statistics</h2>
        <table>
            <thead>
                <tr>
                    <th>Total Students</th>
                    <th>Submitted Students</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo $submission_stats['totalStudents']; ?></td>
                    <td><?php echo $submission_stats['submittedStudents']; ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Detailed Marks Table -->
        <h2>Detailed Student Marks</h2>
        <table>
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Marks Obtained</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assignment_details as $detail): ?>
                    <tr>
                        <td><?php echo $detail['StudentID']; ?></td>
                        <td><?php echo $detail['StudentName']; ?></td>
                        <td><?php echo $detail['MarksObtained'] !== null ? $detail['MarksObtained'] : 'Not Submitted'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
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
