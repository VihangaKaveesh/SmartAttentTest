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

$module_id = $_GET['module_id'] ?? null;
$assignment_id = $_GET['assignment_id'] ?? null;
$average_marks_data = [];
$assignment_details = [];

// Get average marks for all assignments under the selected module
if ($module_id) {
    $average_marks_data = getAverageMarks($module_id);
}

// Get specific assignment details if an assignment is selected
if ($assignment_id) {
    $assignment_details = getAssignmentDetails($assignment_id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marks Analysis</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<h1>Marks Analysis for Module</h1>

<!-- Average marks chart -->
<canvas id="averageMarksChart" width="400" height="200"></canvas>

<script>
    const ctx = document.getElementById('averageMarksChart').getContext('2d');
    const averageMarksData = <?php echo json_encode($average_marks_data); ?>;

    const labels = averageMarksData.map(item => item.AssignmentName);
    const data = averageMarksData.map(item => parseFloat(item.averageMarks));

    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Average Marks',
                data: data,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
</script>

<!-- Assignment dropdown for individual analysis -->
<form method="GET" action="marksAnalysis.php">
    <input type="hidden" name="module_id" value="<?php echo $module_id; ?>">
    <label for="assignment">Select Assignment:</label>
    <select name="assignment_id" id="assignment" onchange="this.form.submit()">
        <option value="">Select an assignment</option>
        <?php
        $assignments = getAssignments($module_id);
        foreach ($assignments as $assignment) {
            $selected = ($assignment['AssignmentID'] == $assignment_id) ? 'selected' : '';
            echo "<option value='{$assignment['AssignmentID']}' $selected>{$assignment['AssignmentName']}</option>";
        }
        ?>
    </select>
</form>

<?php if ($assignment_id && !empty($assignment_details)): ?>
    <h2>Marks for Assignment</h2>
    <table border="1">
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Marks Obtained</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($assignment_details as $detail): ?>
                <tr>
                    <td><?php echo htmlspecialchars($detail['StudentName']); ?></td>
                    <td><?php echo htmlspecialchars($detail['MarksObtained']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php elseif ($assignment_id): ?>
    <p>No marks found for this assignment.</p>
<?php endif; ?>

</body>
</html>
