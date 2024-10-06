<?php
session_start();

// Check if the user is logged in as a teacher
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login/login.html");
    exit();
}

// Function to connect to the database
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

// Create a connection
$conn = connectDB();

// Fetch teacher data from the database
$teacher_id = $_SESSION['teacher_id'];
$sql = "SELECT FirstName, LastName, Email, PhoneNumber, Username FROM teachers WHERE TeacherID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Teacher not found.");
}

$teacher = $result->fetch_assoc();
$stmt->close();

// Fetch module names based on TeacherID
$module_names = [];
$module_sql = "SELECT ModuleName FROM modules WHERE ModuleID IN (
                SELECT ModuleID FROM modules WHERE TeacherID = ?
              )";
$module_stmt = $conn->prepare($module_sql);
$module_stmt->bind_param("i", $teacher_id);
$module_stmt->execute();
$module_result = $module_stmt->get_result();

while ($module = $module_result->fetch_assoc()) {
    $module_names[] = $module['ModuleName'];
}
$module_stmt->close();

$conn->close();

// Combine first name and last name
$full_name = htmlspecialchars($teacher['FirstName'] . ' ' . $teacher['LastName']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
            text-align: center;
        }
        .profile {
            background-color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            display: inline-block;
        }
        h1 {
            color: #4CAF50;
        }
        p {
            font-size: 18px;
            margin: 10px 0;
        }
    </style>
</head>
<body>

<h1>Teacher Profile</h1>

<div class="profile">
    <p><strong>Name:</strong> <?php echo $full_name; ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($teacher['Email']); ?></p>
    <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($teacher['PhoneNumber']); ?></p>
    <p><strong>Username:</strong> <?php echo htmlspecialchars($teacher['Username']); ?></p>
    <p><strong>Modules:</strong> <?php echo htmlspecialchars(implode(', ', $module_names)); ?></p>
</div>

</body>
</html>
