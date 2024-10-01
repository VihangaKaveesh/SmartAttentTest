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


// Handle form submission for adding/updating teachers
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = connectDB();
    $message = [];

    $student_id = isset($_POST['teacher_id']) ? $_POST['teacher_id'] : '';
    $username = $_POST['username'];
    $password = $_POST['password'];  // No encryption applied here
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];

    // Validate required fields
    if (empty($username) || empty($password) || empty($name) || empty($email) || empty($phone_number)) {
        $message[] = 'Error: All fields are required.';
    } else {
        if (isset($_POST['update_teacher'])) {
            // Update teacher
            $stmt = $conn->prepare("UPDATE teachers SET username = ?, password = ?, name = ?, email = ?, phone_number = ? WHERE teacher_id = ?");
            $stmt->bind_param("sssssi", $username, $password, $name, $email, $phone_number, $student_id);
            if ($stmt->execute()) {
                $message[] = 'Teacher updated successfully!';
                // Redirect to reset the form to "Add Teacher" mode
                header("Location: manageTeachers.php"); 
                exit(); // Exit after redirection
            } else {
                $message[] = 'Error: Could not update teacher.';
            }
            $stmt->close();
        } else {
            // Add new teacher
            $stmt = $conn->prepare("INSERT INTO teachers (username, password, name, email, phone_number) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $password, $name, $email, $phone_number);
            if ($stmt->execute()) {
                $message[] = 'Teacher added successfully!';
            } else {
                $message[] = 'Error: Could not add teacher.';
            }
            $stmt->close();
        }
    }

    $conn->close();
}

// Handle deleting teachers
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $conn = connectDB();
    $teacher_id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM teachers WHERE teacher_id = ?");
    $stmt->bind_param("i", $teacher_id);
    if ($stmt->execute()) {
        $message[] = 'Teacher deleted successfully!';
    } else {
        $message[] = 'Error: Could not delete teacher.';
    }
    $stmt->close();
    $conn->close();
}

// If editing, fetch teacher data
$teacher = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $conn = connectDB();
    $teacher_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE teacher_id = ?");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $teacher = $result->fetch_assoc();
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
    <title>Teacher Management</title>
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

<h1>Manage Teachers</h1>

<?php
// Display any messages or errors
if (!empty($message)) {
    foreach ($message as $msg) {
        echo '<div class="' . (strpos($msg, 'Error') !== false ? 'error' : 'message') . '">' . htmlspecialchars($msg) . '</div>';
    }
}
?>

<!-- Form for adding or updating a teacher -->
<form method="POST">
    <input type="hidden" name="teacher_id" value="<?php echo isset($teacher['teacher_id']) ? $teacher['teacher_id'] : ''; ?>">
    <label for="username">Username:</label>
    <input type="text" name="username" required value="<?php echo isset($teacher['username']) ? htmlspecialchars($teacher['username']) : ''; ?>"><br>
    <label for="password">Password:</label>
    <input type="password" name="password" required value="<?php echo isset($teacher['password']) ? htmlspecialchars($teacher['password']) : ''; ?>"><br>
    <label for="name">Name:</label>
    <input type="text" name="name" required value="<?php echo isset($teacher['name']) ? htmlspecialchars($teacher['name']) : ''; ?>"><br>
    <label for="email">Email:</label>
    <input type="email" name="email" required value="<?php echo isset($teacher['email']) ? htmlspecialchars($teacher['email']) : ''; ?>"><br>
    <label for="phone_number">Phone Number:</label>
    <input type="text" name="phone_number" required value="<?php echo isset($teacher['phone_number']) ? htmlspecialchars($teacher['phone_number']) : ''; ?>"><br>
    <label for="course">Course:</label>
    
    <input type="submit" name="<?php echo isset($teacher) ? 'update_teacher' : 'add_teacher'; ?>" value="<?php echo isset($teacher) ? 'Update Teacher' : 'Add Teacher'; ?>">
</form>

<!-- Table to display the list of teachers -->
<h2>Teachers List</h2>
<table>
    <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Name</th>
        <th>Email</th>
        <th>Phone Number</th>
        <th>Actions</th>
    </tr>
    <?php
    // Fetch and display the list of teachers
    $conn = connectDB();  // Connect to the database
    $result = $conn->query("SELECT teacher_id, username, name, email, phone_number FROM teachers");

    // Loop through the results and display each teacher in a table row
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['teacher_id']) . '</td>';
            echo '<td>' . htmlspecialchars($row['username']) . '</td>';
            echo '<td>' . htmlspecialchars($row['name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['email']) . '</td>';
            echo '<td>' . htmlspecialchars($row['phone_number']) . '</td>';
            echo '<td>';
            echo '<a href="?action=edit&id=' . htmlspecialchars($row['teacher_id']) . '">Edit</a> | ';
            echo '<a href="?action=delete&id=' . htmlspecialchars($row['teacher_id']) . '" onclick="return confirm(\'Are you sure you want to delete this teacher?\');">Delete</a>';
            echo '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="7">No teachers found.</td></tr>';
    }

    $conn->close();  // Close the database connection
    ?>
</table>

</body>
</html>
