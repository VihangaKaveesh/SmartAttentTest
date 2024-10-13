<?php
// Database connection
$host = 'localhost'; // Change as necessary
$user = 'root';
$password = '';
$dbname = 'smartattendtest'; // Update with your database name

$conn = new mysqli($host, $user, $password, $dbname);

// Check for connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the module ID from the URL (or session or form submission)
$module_id = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;
if ($module_id == 0) {
    die("Invalid module ID");
}

// Fetch the total number of students in the module
$sql_students = "SELECT COUNT(*) AS total_students FROM students WHERE ModuleID = ?";
$stmt_students = $conn->prepare($sql_students);
$stmt_students->bind_param('i', $module_id);
$stmt_students->execute();
$result_students = $stmt_students->get_result();
$row_students = $result_students->fetch_assoc();
$total_students = $row_students['total_students'];

// Fetch the total number of sessions conducted for the module
$sql_sessions = "SELECT COUNT(*) AS total_sessions FROM sessions WHERE ModuleID = ?";
$stmt_sessions = $conn->prepare($sql_sessions);
$stmt_sessions->bind_param('i', $module_id);
$stmt_sessions->execute();
$result_sessions = $stmt_sessions->get_result();
$row_sessions = $result_sessions->fetch_assoc();
$total_sessions = $row_sessions['total_sessions'];

// Fetch the attendance records for the module
$sql_attendance = "
    SELECT 
        students.StudentID, 
        CONCAT(students.FirstName, ' ', students.LastName) AS StudentName,
        COUNT(attendance.AttendanceID) AS present_count
    FROM students
    LEFT JOIN attendance ON students.StudentID = attendance.StudentID
    LEFT JOIN sessions ON attendance.SessionID = sessions.SessionID
    WHERE students.ModuleID = ? AND attendance.Status = 'present'
    GROUP BY students.StudentID
";
$stmt_attendance = $conn->prepare($sql_attendance);
$stmt_attendance->bind_param('i', $module_id);
$stmt_attendance->execute();
$result_attendance = $stmt_attendance->get_result();

// Create arrays for chart data
$students = [];
$present_counts = [];

// Process the attendance data
while ($row = $result_attendance->fetch_assoc()) {
    $students[] = $row['StudentName'];
    $present_counts[] = $row['present_count'];
}

// Close connections
$stmt_students->close();
$stmt_sessions->close();
$stmt_attendance->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <title>Module Attendance Analysis</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            width: 80%;
            margin: 0 auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: center;
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
        <a href="../login/login.php">Logout</a>
    </div>
</div>
    <div class="container">
        <h1>Attendance Analysis for Module ID: <?php echo $module_id; ?></h1>
        
        <h3>Total Students in Module: <?php echo $total_students; ?></h3>
        <h3>Total Sessions Conducted: <?php echo $total_sessions; ?></h3>
        
        <h2>Student Attendance Details</h2>
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Attendance Count</th>
                    <th>Attendance Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $index => $student_name): 
                    $attendance_percentage = ($present_counts[$index] / $total_sessions) * 100;
                ?>
                <tr>
                    <td><?php echo $student_name; ?></td>
                    <td><?php echo $present_counts[$index]; ?></td>
                    <td><?php echo number_format($attendance_percentage, 2); ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h2>Attendance Analysis Chart</h2>
        <canvas id="attendanceChart"></canvas>
    </div>
    <script>
    // Toggle Sidebar
    const hamburger = document.querySelector('.hamburger');
    const sidebar = document.querySelector('.sidebar');

    hamburger.addEventListener('click', function() {
        sidebar.classList.toggle('active');
    });

        var ctx = document.getElementById('attendanceChart').getContext('2d');
        var chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($students); ?>,
                datasets: [{
                    label: 'Attendance Count',
                    data: <?php echo json_encode($present_counts); ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Sessions Attended'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Students'
                        }
                    }
                }
            }
        });

    </script>
</body>
</html>
