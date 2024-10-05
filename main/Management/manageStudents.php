<?php
session_start();

// Check if the user is logged in as management
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'management') {
    // If not logged in or not management, redirect to the login page
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

// Fetch modules from the database
function fetchModules($conn) {
    $modules = array();
    $result = $conn->query("SELECT ModuleID, ModuleName FROM modules");

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $modules[] = $row;
        }
    }

    return $modules;
}

// Check if username or email already exists
function isUsernameOrEmailExists($conn, $username, $email, $student_id = null) {
    $query = "SELECT * FROM students WHERE (Username = ? OR Email = ?)";
    if ($student_id) {
        $query .= " AND StudentID != ?";  // Exclude the current student in case of updates
    }
    $stmt = $conn->prepare($query);
    if ($student_id) {
        $stmt->bind_param("ssi", $username, $email, $student_id);
    } else {
        $stmt->bind_param("ss", $username, $email);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Handle form submission for adding/updating students
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = connectDB();
    $message = [];

    $student_id = isset($_POST['student_id']) ? $_POST['student_id'] : '';
    $username = $_POST['username'];
    $password = $_POST['password'];  // No encryption applied here
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $module_id = $_POST['course'];  // Store module_id now

    // Validate required fields
    if (empty($username) || empty($password) || empty($first_name) || empty($last_name) || empty($email) || empty($phone_number) || empty($module_id)) {
        $message[] = 'Error: All fields are required.';
    } elseif (isUsernameOrEmailExists($conn, $username, $email, $student_id)) {
        $message[] = 'Error: Username or Email already exists.';
    } else {
        if (isset($_POST['update_student'])) {
            // Update student
            $stmt = $conn->prepare("UPDATE students SET Username = ?, Password = ?, FirstName = ?, LastName = ?, Email = ?, PhoneNumber = ?, ModuleID = ? WHERE StudentID = ?");
            $stmt->bind_param("ssssssii", $username, $password, $first_name, $last_name, $email, $phone_number, $module_id, $student_id);
            if ($stmt->execute()) {
                $message[] = 'Student updated successfully!';
                // Redirect to reset the form to "Add Student" mode
                header("Location: manageStudents.php"); 
                exit(); // Exit after redirection
            } else {
                $message[] = 'Error: Could not update student.';
            }
            $stmt->close();
        } else {
            // Add new student
            $stmt = $conn->prepare("INSERT INTO students (Username, Password, FirstName, LastName, Email, PhoneNumber, ModuleID) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssi", $username, $password, $first_name, $last_name, $email, $phone_number, $module_id);
            if ($stmt->execute()) {
                $message[] = 'Student added successfully!';
            } else {
                $message[] = 'Error: Could not add student.';
            }
            $stmt->close();
        }
    }

    $conn->close();
}

// Handle deleting students
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $conn = connectDB();
    $student_id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM students WHERE StudentID = ?");
    $stmt->bind_param("i", $student_id);
    if ($stmt->execute()) {
        $message[] = 'Student deleted successfully!';
    } else {
        $message[] = 'Error: Could not delete student.';
    }
    $stmt->close();
    $conn->close();
}

// If editing, fetch student data
$student = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $conn = connectDB();
    $student_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM students WHERE StudentID = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $student = $result->fetch_assoc();
    }
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #FFE3B3;
            color: #26648E;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #53D2DC;
            color: #26648E;
        }
        th, td {
            border: 1px solid #4F8FC0;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #26648E;
            color: #FFE3B3;
        }
        td {
            background-color: #FFE3B3;
        }
        .message {
            color: green;
        }
        .error {
            color: red;
        }
        form {
            margin-bottom: 20px;
        }
        input[type="text"], input[type="password"], input[type="email"], select {
            width: 100%;
            padding: 10px;
            margin: 5px 0 10px 0;
            border: 1px solid #4F8FC0;
            border-radius: 4px;
            background-color: #FFE3B3;
            color: #26648E;
        }
        input[type="submit"] {
            background-color: #26648E;
            color: #FFE3B3;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #4F8FC0;
        }
    </style>
</head>
<body>

<h1>Manage Students</h1>

<?php
// Display any messages or errors
if (!empty($message)) {
    foreach ($message as $msg) {
        echo '<div class="' . (strpos($msg, 'Error') !== false ? 'error' : 'message') . '">' . htmlspecialchars($msg) . '</div>';
    }
}
?>

<!-- Form for adding or updating a student -->
<form method="POST">
    <input type="hidden" name="student_id" value="<?php echo isset($student['StudentID']) ? $student['StudentID'] : ''; ?>">
    <label for="username">Username:</label>
    <input type="text" name="username" required value="<?php echo isset($student['Username']) ? htmlspecialchars($student['Username']) : ''; ?>"><br>
    <label for="password">Password:</label>
    <input type="password" name="password" required value="<?php echo isset($student['Password']) ? htmlspecialchars($student['Password']) : ''; ?>"><br>
    <label for="first_name">First Name:</label>
    <input type="text" name="first_name" required value="<?php echo isset($student['FirstName']) ? htmlspecialchars($student['FirstName']) : ''; ?>"><br>
    <label for="last_name">Last Name:</label>
    <input type="text" name="last_name" required value="<?php echo isset($student['LastName']) ? htmlspecialchars($student['LastName']) : ''; ?>"><br>
    <label for="email">Email:</label>
    <input type="email" name="email" required value="<?php echo isset($student['Email']) ? htmlspecialchars($student['Email']) : ''; ?>"><br>
    <label for="phone_number">Phone Number:</label>
    <input type="text" name="phone_number" required value="<?php echo isset($student['PhoneNumber']) ? htmlspecialchars($student['PhoneNumber']) : ''; ?>"><br>
    <label for="course">Course:</label>
    <select name="course" required>
        <option value="">Select Course</option>
        <?php
        $conn = connectDB();
        $modules = fetchModules($conn);
        foreach ($modules as $module) {
            echo '<option value="' . htmlspecialchars($module['ModuleID']) . '"';
            if (isset($student['ModuleID']) && $student['ModuleID'] == $module['ModuleID']) {
                echo ' selected';
            }
            echo '>' . htmlspecialchars($module['ModuleName']) . '</option>';
        }
        $conn->close();
        ?>
    </select><br>
    <input type="submit" name="<?php echo isset($student['StudentID']) ? 'update_student' : 'add_student'; ?>" value="<?php echo isset($student['StudentID']) ? 'Update Student' : 'Add Student'; ?>">
</form>

<!-- Display students table -->
<table>
    <thead>
        <tr>
            <th>Username</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Email</th>
            <th>Phone Number</th>
            <th>Module</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $conn = connectDB();
        $result = $conn->query("SELECT students.*, modules.ModuleName FROM students LEFT JOIN modules ON students.ModuleID = modules.ModuleID");

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['Username']) . '</td>';
                echo '<td>' . htmlspecialchars($row['FirstName']) . '</td>';
                echo '<td>' . htmlspecialchars($row['LastName']) . '</td>';
                echo '<td>' . htmlspecialchars($row['Email']) . '</td>';
                echo '<td>' . htmlspecialchars($row['PhoneNumber']) . '</td>';
                echo '<td>' . htmlspecialchars($row['ModuleName']) . '</td>';
                echo '<td>
                    <a href="?action=edit&id=' . $row['StudentID'] . '">Edit</a> | 
                    <a href="?action=delete&id=' . $row['StudentID'] . '" onclick="return confirm(\'Are you sure you want to delete this student?\')">Delete</a> | 
                    <a href="attendanceAnalysis.php?student_id=' . $row['StudentID'] . '">View Attendance</a>
                </td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="7">No students found.</td></tr>';
        }

        $conn->close();
        ?>
    </tbody>
</table>

</body>
</html>