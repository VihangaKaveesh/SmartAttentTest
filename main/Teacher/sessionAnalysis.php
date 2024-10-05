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

// Fetch session attendance data
function fetchSessionAttendance($conn, $session_id) {
    $attendance_data = [];

    // Modify query to include all students in the module even if absent
    $query = "
        SELECT 
            st.FirstName, 
            st.LastName, 
            COALESCE(a.Status, 'absent') AS Status
        FROM 
            Students st
        LEFT JOIN 
            Attendance a ON st.StudentID = a.StudentID AND a.SessionID = ?
        JOIN 
            Modules m ON st.ModuleID = m.ModuleID
        JOIN 
            Sessions s ON s.ModuleID = m.ModuleID
        WHERE 
            s.SessionID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $session_id, $session_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $attendance_data[] = $row;
    }
    
    return $attendance_data;
}

// Fetch session details
function fetchSessionDetails($conn, $session_id) {
    $query = "
        SELECT 
            s.SessionDate, 
            l.LabName, 
            m.ModuleName, 
            t.FirstName AS TeacherFirstName, 
            t.LastName AS TeacherLastName
        FROM 
            Sessions s
        JOIN 
            Labs l ON s.LabID = l.LabID
        JOIN 
            Modules m ON s.ModuleID = m.ModuleID
        JOIN 
            Teachers t ON s.TeacherID = t.TeacherID
        WHERE 
            s.SessionID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $session_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

// Get the session ID from the URL parameter
$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;
if ($session_id == 0) {
    echo "Invalid Session ID";
    exit();
}

$conn = connectDB();
$session_details = fetchSessionDetails($conn, $session_id);
$attendance_data = fetchSessionAttendance($conn, $session_id);
$conn->close();

// Calculate attendance details
$total_students = count($attendance_data);
$present_count = count(array_filter($attendance_data, function($attendance) {
    return $attendance['Status'] === 'present';
}));
$attendance_percentage = ($total_students > 0) ? ($present_count / $total_students) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
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
    width: 300px !important; /* Chart size */
    height: 300px !important;
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

    </style>
</head>
<body>
    <div class="container">
        <h1>Session Analysis for <?php echo $session_details['ModuleName']; ?></h1>
        <p>Teacher: <?php echo $session_details['TeacherFirstName'] . ' ' . $session_details['TeacherLastName']; ?></p>
        <p>Lab: <?php echo $session_details['LabName']; ?></p>
        <p>Session Date: <?php echo date("Y-m-d H:i:s", strtotime($session_details['SessionDate'])); ?></p>
        <p>Total Students in Module: <?php echo $total_students; ?></p>
        <p>Students Attended: <?php echo $present_count; ?></p>
        <p>Attendance Percentage: <?php echo number_format($attendance_percentage, 2); ?>%</p>

        <!-- Bar Chart to Display Attendance in this Session -->
        <div class="chart-container">
            <canvas id="attendanceBarChart" width="300" height="300"></canvas>
        </div>

        <script>
            var ctx = document.getElementById('attendanceBarChart').getContext('2d');
            var attendanceBarChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Attended', 'Absent'],
                    datasets: [{
                        label: 'Students Attendance',
                        data: [<?php echo $present_count; ?>, <?php echo $total_students - $present_count; ?>],
                        backgroundColor: ['#4CAF50', '#F44336'],
                        borderColor: ['#388E3C', '#D32F2F'],
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        </script>

        <!-- Display Detailed Attendance List in a Table -->
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
                        <td><?php echo $attendance['FirstName'] . ' ' . $attendance['LastName']; ?></td>
                        <td><?php echo ucfirst($attendance['Status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
