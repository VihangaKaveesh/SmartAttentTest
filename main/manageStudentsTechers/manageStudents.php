<?php

// Function to connect to the database
function connectDB() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "smartattendtest";

    // Create a new connection object
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check if connection was successful
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;  // Return the connection object
}

// Fetch modules from the database
function fetchClasses($conn) {
    $classes = array();
    $result = $conn->query("SELECT module_name FROM modules");

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $classes[] = $row;
        }
    }

    return $classes;
}

// Check if username or email already exists
function isUsernameOrEmailExists($conn, $username, $email, $student_id = null) {
    $query = "SELECT * FROM students WHERE (username = ? OR email = ?)";
    if ($student_id) {
        $query .= " AND student_id != ?";  // Exclude the current student in case of updates
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
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $course = $_POST['course'];

    // Validate required fields
    if (empty($username) || empty($password) || empty($name) || empty($email) || empty($phone_number) || empty($course)) {
        $message[] = 'Error: All fields are required.';
    } elseif (isUsernameOrEmailExists($conn, $username, $email, $student_id)) {
        $message[] = 'Error: Username or Email already exists.';
    } else {
        if (isset($_POST['update_student'])) {
            // Update student
            $stmt = $conn->prepare("UPDATE students SET username = ?, password = ?, name = ?, email = ?, phone_number = ?, course = ? WHERE student_id = ?");
            $stmt->bind_param("ssssssi", $username, $password, $name, $email, $phone_number, $course, $student_id);
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
            $stmt = $conn->prepare("INSERT INTO students (username, password, name, email, phone_number, course) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $username, $password, $name, $email, $phone_number, $course);
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
    $stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
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
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
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
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .message {
            color: green;
        }
        .error {
            color: red;
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
    <input type="hidden" name="student_id" value="<?php echo isset($student['student_id']) ? $student['student_id'] : ''; ?>">
    <label for="username">Username:</label>
    <input type="text" name="username" required value="<?php echo isset($student['username']) ? htmlspecialchars($student['username']) : ''; ?>"><br>
    <label for="password">Password:</label>
    <input type="password" name="password" required value="<?php echo isset($student['password']) ? htmlspecialchars($student['password']) : ''; ?>"><br>
    <label for="name">Name:</label>
    <input type="text" name="name" required value="<?php echo isset($student['name']) ? htmlspecialchars($student['name']) : ''; ?>"><br>
    <label for="email">Email:</label>
    <input type="email" name="email" required value="<?php echo isset($student['email']) ? htmlspecialchars($student['email']) : ''; ?>"><br>
    <label for="phone_number">Phone Number:</label>
    <input type="text" name="phone_number" required value="<?php echo isset($student['phone_number']) ? htmlspecialchars($student['phone_number']) : ''; ?>"><br>
    <label for="course">Course:</label>
    <select name="course" required>
        <option value="">Select Course</option>
        <?php
        $conn = connectDB();  // Connect to the database
        $classes = fetchClasses($conn);  // Fetch modules for the dropdown

        // Populate the course dropdown with options
        foreach ($classes as $class) {
            $selected = (isset($student['course']) && $student['course'] == $class['module_name']) ? 'selected' : '';
            echo '<option value="' . htmlspecialchars($class['module_name']) . '" ' . $selected . '>' . htmlspecialchars($class['module_name']) . '</option>';
        }
        $conn->close();  // Close the database connection
        ?>
    </select><br>
    <input type="submit" name="<?php echo isset($student) ? 'update_student' : 'add_student'; ?>" value="<?php echo isset($student) ? 'Update Student' : 'Add Student'; ?>">
</form>

<!-- Table to display the list of students -->
<h2>Students List</h2>
<table>
    <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Name</th>
        <th>Email</th>
        <th>Phone Number</th>
        <th>Course</th>
        <th>Actions</th>
    </tr>
    <?php
    // Fetch and display the list of students
    $conn = connectDB();  // Connect to the database
    $result = $conn->query("SELECT student_id, username, name, email, phone_number, course FROM students");

    // Loop through the results and display them
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['student_id']) . '</td>';
            echo '<td>' . htmlspecialchars($row['username']) . '</td>';
            echo '<td>' . htmlspecialchars($row['name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['email']) . '</td>';
            echo '<td>' . htmlspecialchars($row['phone_number']) . '</td>';
            echo '<td>' . htmlspecialchars($row['course']) . '</td>';
            echo '<td>';
            echo '<a href="?action=edit&id=' . $row['student_id'] . '">Edit</a> | ';
            echo '<a href="?action=delete&id=' . $row['student_id'] . '" onclick="return confirm(\'Are you sure?\')">Delete</a>';
            echo '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="7">No students found.</td></tr>';
    }
    $conn->close();  // Close the database connection
    ?>
</table>

</body>
</html>
