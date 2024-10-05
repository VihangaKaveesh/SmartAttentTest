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
function fetchSessionAttendance($conn, $session_id) {
    $query = "
        SELECT 
            st.FirstName, st.LastName, a.Status 
        FROM 
            Attendance a
        JOIN 
            Students st ON a.StudentID = st.StudentID
        WHERE 
            a.SessionID = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $session_id);
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

    $attendance_data = fetchSessionAttendance($conn, $session_id);
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Attendance Analysis</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Same CSS styles for general layout */
    </style>
</head>
<body>
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
</body>
</html>
