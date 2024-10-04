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

// Fetch the student's attendance data along with session details for their module
function fetchStudentAttendance($conn, $student_id) {
    $attendance_data = [];

    // Query to fetch sessions for the student's module and attendance status
    $query = "
        SELECT 
            s.SessionDate, 
            a.Status 
        FROM 
            Sessions s
        LEFT JOIN 
            Attendance a ON s.SessionID = a.SessionID AND a.StudentID = ?
        JOIN 
            Students st ON st.ModuleID = s.ModuleID
        WHERE 
            st.StudentID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $student_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $attendance_data[] = $row;
    }
    
    return $attendance_data;
}

// Fetch student's name
function fetchStudentName($conn, $student_id) {
    $query = "SELECT FirstName, LastName FROM Students WHERE StudentID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['FirstName'] . ' ' . $row['LastName'];
    }

    return "Unknown Student";
}

// Get the student ID from the URL parameter
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
if ($student_id == 0) {
    echo "Invalid Student ID";
    exit();
}

$conn = connectDB();
$attendance_data = fetchStudentAttendance($conn, $student_id);
$student_name = fetchStudentName($conn, $student_id);
$conn->close();

// Calculate attendance details
$total_classes = count($attendance_data);
$present_count = count(array_filter($attendance_data, function($attendance) {
    return $attendance['Status'] === 'present';
}));
$attendance_percentage = ($total_classes > 0) ? ($present_count / $total_classes) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Analysis</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Attendance Analysis for <?php echo $student_name; ?></h1>
        <p>Total Classes Conducted: <?php echo $total_classes; ?></p>
        <p>Classes Attended: <?php echo $present_count; ?></p>
        <p>Attendance Percentage: <?php echo number_format($attendance_percentage, 2); ?>%</p>

        <!-- Center the attendance data as a pie chart -->
        <div class="chart-container">
            <canvas id="attendanceChart" width="300" height="300"></canvas>
        </div>

        <script>
            var ctx = document.getElementById('attendanceChart').getContext('2d');
            var attendanceChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Present', 'Absent'],
                    datasets: [{
                        data: [<?php echo $present_count; ?>, <?php echo $total_classes - $present_count; ?>],
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

        <!-- Display Attendance Details in a Table -->
        <table>
            <thead>
                <tr>
                    <th>Session Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attendance_data as $attendance): ?>
                    <tr>
                        <td><?php echo date("Y-m-d H:i:s", strtotime($attendance['SessionDate'])); ?></td>
                        <td><?php echo isset($attendance['Status']) ? ucfirst($attendance['Status']) : 'Absent'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
