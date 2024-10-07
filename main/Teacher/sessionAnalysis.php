<?php 
// Include the database connection
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

// Fetch all sessions taught by the teacher
function fetchTeacherSessions($conn, $teacher_id) {
    $query = "
        SELECT 
            s.SessionID, s.SessionDate, m.ModuleName, l.LabName 
        FROM 
            Sessions s
        JOIN 
            Modules m ON s.ModuleID = m.ModuleID
        JOIN 
            Labs l ON s.LabID = l.LabID
        WHERE 
            s.TeacherID = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $sessions = [];
    while ($row = $result->fetch_assoc()) {
        $sessions[] = $row;
    }
    return $sessions;
}

// Fetch session details and attendance data for the selected session
// Fetch session details and attendance data for the selected session
function fetchSessionAttendance($conn, $session_id, $module_id) {
    $query = "
        SELECT 
            st.FirstName, st.LastName, IFNULL(a.Status, 'absent') AS Status
        FROM 
            Students st
        LEFT JOIN 
            Attendance a ON st.StudentID = a.StudentID AND a.SessionID = ?
        WHERE 
            st.ModuleID = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $session_id, $module_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $attendance_data = [];
    while ($row = $result->fetch_assoc()) {
        $attendance_data[] = $row;
    }
    return $attendance_data;
}


// Fetch total number of students enrolled in the module for the session
function fetchTotalStudentsInModule($conn, $module_id) {
    $query = "SELECT COUNT(*) as total_students FROM Students WHERE ModuleID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $module_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['total_students'];
}

// Get the teacher ID from session (after login)
session_start();
$teacher_id = isset($_SESSION['teacher_id']) ? $_SESSION['teacher_id'] : 0;

if ($teacher_id == 0) {
    echo "Unauthorized access!";
    exit();
}

// Get selected session ID from URL (if provided)
$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;

$conn = connectDB();

// Fetch sessions taught by the teacher
$sessions = fetchTeacherSessions($conn, $teacher_id);

// If a session is selected, fetch attendance data
$attendance_data = [];
$module_id = 0;
$lab_name = '';
$session_date = '';
$total_students = 0;
$attendance_percentage = 0;

if ($session_id > 0) {
    $session_details = $conn->query("SELECT ModuleID, LabID, SessionDate FROM Sessions WHERE SessionID = $session_id")->fetch_assoc();
    $module_id = $session_details['ModuleID'];
    $lab_name = $conn->query("SELECT LabName FROM Labs WHERE LabID = {$session_details['LabID']}")->fetch_assoc()['LabName'];
    $session_date = $session_details['SessionDate'];

    // Fetch attendance data with all students for the selected session
    $attendance_data = fetchSessionAttendance($conn, $session_id, $module_id);
    $total_students = fetchTotalStudentsInModule($conn, $module_id);

    // Calculate attendance percentage
    $present_count = count(array_filter($attendance_data, function($attendance) {
        return $attendance['Status'] === 'present';
    }));
    $attendance_percentage = ($total_students > 0) ? ($present_count / $total_students) * 100 : 0;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
      <!-- Font Awesome for icons -->
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts for the Orbitron font -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Attendance Analysis</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
       body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #e9ecef;
    margin: 0;
    padding: 20px;
    color: #333;
}

h1 {
    text-align: center;
    color: #0056b3;
    font-size: 2.5em;
    margin-bottom: 20px;
}

.container {
    max-width: 80%;
    margin: 0 auto;
    background: #ffffff;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    animation: fadeIn 0.5s ease-in-out;
}

p {
    font-size: 1.2em;
    color: #555;
    margin: 10px 0;
    text-align: center;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
    background: #f8f9fa;
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
    letter-spacing: 0.05em;
}

td {
    color: #333;
}

tr:nth-child(even) {
    background-color: #f1f1f1;
}

tr:hover {
    background-color: #f8f9fa;
    cursor: pointer;
}

.chart-container {
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 40px 0;
    height: auto;
}

canvas {
    width: 500px !important; /* Chart size */
    height: 500px !important;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2); /* Optional for depth effect */
}

.summary {
    text-align: center;
    margin: 30px 0;
    font-size: 1.3em;
    color: #6c757d;
}

@keyframes fadeIn {
    0% { opacity: 0; }
    100% { opacity: 1; }
}

/* MEDIA QUERIES FOR RESPONSIVENESS */

/* Tablets and smaller devices */
@media (max-width: 1024px) {
    .container {
        max-width: 95%;
        padding: 20px;
    }

    h1 {
        font-size: 2em;
    }

    p {
        font-size: 1.1em;
    }

    th, td {
        font-size: 1em;
        padding: 10px;
    }

    .chart-container {
        flex-direction: column;
        margin: 30px 0;
    }

    canvas {
        width: 250px !important;
        height: 250px !important;
    }
}

/* Mobile devices */
@media (max-width: 768px) {
    .container {
        max-width: 100%;
        padding: 15px;
    }

    h1 {
        font-size: 1.7em;
    }

    p {
        font-size: 1em;
    }

    th, td {
        font-size: 0.9em;
        padding: 8px;
    }

    .chart-container {
        margin: 20px 0;
    }

    canvas {
        width: 200px !important;
        height: 200px !important;
    }

    table {
        font-size: 0.9em;
    }
}

/* Extra small devices */
@media (max-width: 480px) {
    h1 {
        font-size: 1.5em;
    }

    p {
        font-size: 0.9em;
    }

    th, td {
        font-size: 0.8em;
        padding: 6px;
    }

    canvas {
        width: 180px !important;
        height: 180px !important;
    }

    table {
        font-size: 0.85em;
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
        <h1>Attendance Analysis</h1>

        <!-- Dropdown to select a session -->
        <form method="GET">
            <label for="session_id">Select a Session:</label>
            <select name="session_id" id="session_id" onchange="this.form.submit()">
                <option value="">-- Select Session --</option>
                <?php foreach ($sessions as $session): ?>
                    <option value="<?php echo $session['SessionID']; ?>" <?php echo ($session['SessionID'] == $session_id) ? 'selected' : ''; ?>>
                        <?php echo "{$session['ModuleName']} ({$session['LabName']}) - {$session['SessionDate']}"; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if ($session_id > 0): ?>
            <h2>Session Details</h2>
            <p>Session Date: <?php echo $session_date; ?></p>
            <p>Lab Name: <?php echo $lab_name; ?></p>
            <p>Total Students: <?php echo $total_students; ?></p>
            <p>Attendance Percentage: <?php echo number_format($attendance_percentage, 2); ?>%</p>

            <!-- Bar Chart for Attendance Analysis -->
            <div class="chart-container">
                <canvas id="attendanceChart" width="400" height="300"></canvas>
            </div>

            <script>
                var ctx = document.getElementById('attendanceChart').getContext('2d');
                var attendanceChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Present', 'Absent'],
                        datasets: [{
                            label: 'Attendance',
                            data: [<?php echo $present_count; ?>, <?php echo $total_students - $present_count; ?>],
                            backgroundColor: ['#4CAF50', '#F44336'],
                            borderColor: ['#388E3C', '#D32F2F'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false,
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            </script>

            <!-- Table for Attendance Details -->
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance_data as $attendance): ?>
                        <tr>
                            <td><?php echo "{$attendance['FirstName']} {$attendance['LastName']}"; ?></td>
                            <td><?php echo ucfirst($attendance['Status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <script>
    document.querySelector('.hamburger').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('active');
    });
</script>
</body>
</html>
