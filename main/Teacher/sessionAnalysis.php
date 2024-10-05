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

// Fetch assignments for a module
function fetchAssignments($conn, $module_id) {
    $query = "SELECT AssignmentID, AssignmentName FROM assignments WHERE ModuleID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $module_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $assignments = [];
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    return $assignments;
}

// Fetch average marks for all assignments under a module
function fetchAverageMarks($conn, $module_id) {
    $query = "
        SELECT a.AssignmentID, a.AssignmentName, AVG(am.MarksObtained) as averageMarks 
        FROM assignments a
        LEFT JOIN assignmentmarks am ON a.AssignmentID = am.AssignmentID 
        WHERE a.ModuleID = ? 
        GROUP BY a.AssignmentID";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $module_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $average_marks = [];
    while ($row = $result->fetch_assoc()) {
        $average_marks[] = $row;
    }
    return $average_marks;
}

// Fetch marks for a specific assignment
function fetchAssignmentMarks($conn, $assignment_id) {
    $query = "
        SELECT st.FirstName, st.LastName, am.MarksObtained 
        FROM assignmentmarks am
        JOIN Students st ON am.StudentID = st.StudentID 
        WHERE am.AssignmentID = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $marks_data = [];
    while ($row = $result->fetch_assoc()) {
        $marks_data[] = $row;
    }
    return $marks_data;
}

// Get the module ID from the URL (if provided)
$module_id = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;

$conn = connectDB();

// Fetch average marks for assignments under the selected module
$average_marks = [];
$assignment_marks_data = [];
$assignments = [];

if ($module_id > 0) {
    $average_marks = fetchAverageMarks($conn, $module_id);
    $assignments = fetchAssignments($conn, $module_id);
}

// Get specific assignment marks if an assignment is selected
$assignment_id = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : 0;

if ($assignment_id > 0) {
    $assignment_marks_data = fetchAssignmentMarks($conn, $assignment_id);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marks Analysis</title>
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

    </style>
</head>
<body>
    <div class="container">
        <h1>Marks Analysis</h1>

        <!-- Dropdown to select an assignment -->
        <form method="GET">
            <label for="assignment_id">Select Assignment:</label>
            <select name="assignment_id" id="assignment_id" onchange="this.form.submit()">
                <option value="">-- Select Assignment --</option>
                <?php foreach ($assignments as $assignment): ?>
                    <option value="<?php echo $assignment['AssignmentID']; ?>" <?php echo ($assignment['AssignmentID'] == $assignment_id) ? 'selected' : ''; ?>>
                        <?php echo $assignment['AssignmentName']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <!-- Bar Chart for Average Marks Analysis -->
        <div class="chart-container">
            <canvas id="averageMarksChart"></canvas>
        </div>

        <script>
            var ctx = document.getElementById('averageMarksChart').getContext('2d');
            var averageMarksChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($average_marks, 'AssignmentName')); ?>,
                    datasets: [{
                        label: 'Average Marks',
                        data: <?php echo json_encode(array_column($average_marks, 'averageMarks')); ?>,
                        backgroundColor: '#007bff',
                        borderColor: '#0056b3',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: true,
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

        <?php if ($assignment_id > 0): ?>
            <h2>Assignment Details</h2>
            <table>
                <tr>
                    <th>Student Name</th>
                    <th>Marks Obtained</th>
                </tr>
                <?php foreach ($assignment_marks_data as $data): ?>
                    <tr>
                        <td><?php echo $data['FirstName'] . ' ' . $data['LastName']; ?></td>
                        <td><?php echo $data['MarksObtained']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
