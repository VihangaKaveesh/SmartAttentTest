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

// Fetch the student's attendance data
function fetchStudentAttendance($conn, $student_id) {
    $attendance_data = [];

    $query = "SELECT status, COUNT(*) as count 
              FROM attendance 
              WHERE StudentID = ? 
              GROUP BY status";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $attendance_data[$row['status']] = $row['count'];
    }
    
    return $attendance_data;
}

// Get the student ID from the URL parameter
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
if ($student_id == 0) {
    echo "Invalid Student ID";
    exit();
}

$conn = connectDB();
$attendance_data = fetchStudentAttendance($conn, $student_id);
$conn->close();

// Calculate attendance percentage
$total_classes = isset($attendance_data['present']) ? $attendance_data['present'] + (isset($attendance_data['absent']) ? $attendance_data['absent'] : 0) : 0;
$attendance_percentage = ($total_classes > 0 && isset($attendance_data['present'])) ? ($attendance_data['present'] / $total_classes) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Analysis</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h1>Attendance Analysis for Student ID: <?php echo $student_id; ?></h1>
    <p>Attendance Percentage: <?php echo number_format($attendance_percentage, 2); ?>%</p>

    <!-- Display the attendance data as a pie chart -->
    <canvas id="attendanceChart" width="400" height="400"></canvas>

    <script>
        var ctx = document.getElementById('attendanceChart').getContext('2d');
        var attendanceChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Present', 'Absent'],
                datasets: [{
                    data: [<?php echo isset($attendance_data['present']) ? $attendance_data['present'] : 0; ?>, <?php echo isset($attendance_data['absent']) ? $attendance_data['absent'] : 0; ?>],
                    backgroundColor: ['#4CAF50', '#F44336'],
                    borderColor: ['#388E3C', '#D32F2F'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    </script>
</body>
</html>
