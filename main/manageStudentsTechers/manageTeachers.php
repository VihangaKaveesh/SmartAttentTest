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

// Function to check if a username or email already exists (for both adding and updating)
function isUsernameOrEmailExists($conn, $username, $email, $teacher_id = null) {
    $query = "SELECT TeacherID FROM teachers WHERE (Username = ? OR Email = ?)";
    if ($teacher_id) {
        // Exclude the current teacher from the check (useful during update)
        $query .= " AND TeacherID != ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $username, $email, $teacher_id);
    } else {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $username, $email);
    }
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}

// Validate if the input only contains letters
function isValidName($name) {
    return preg_match("/^[a-zA-Z]+$/", $name);
}

// Handle form submission for adding/updating teachers
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = connectDB();
    $message = [];

    $teacher_id = isset($_POST['teacher_id']) ? $_POST['teacher_id'] : '';
    $username = $_POST['username'];
    $password = $_POST['password'];  // No encryption applied here
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];

    // Validate required fields
    if (empty($username) || empty($password) || empty($first_name) || empty($last_name) || empty($email) || empty($phone_number)) {
        $message[] = 'Error: All fields are required.';
    } elseif (!isValidName($first_name) || !isValidName($last_name)) {
        $message[] = 'Error: First name and last name must contain only letters.';
    } else {
        // Check if the username or email already exists
        if (isUsernameOrEmailExists($conn, $username, $email, $teacher_id)) {
            $message[] = 'Error: Username or Email already exists.';
        } else {
            if (isset($_POST['update_teacher'])) {
                // Update teacher
                $stmt = $conn->prepare("UPDATE teachers SET Username = ?, Password = ?, FirstName = ?, LastName = ?, Email = ?, PhoneNumber = ? WHERE TeacherID = ?");
                $stmt->bind_param("ssssssi", $username, $password, $first_name, $last_name, $email, $phone_number, $teacher_id);
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
                $stmt = $conn->prepare("INSERT INTO teachers (Username, Password, FirstName, LastName, Email, PhoneNumber) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $username, $password, $first_name, $last_name, $email, $phone_number);
                if ($stmt->execute()) {
                    $message[] = 'Teacher added successfully!';
                } else {
                    $message[] = 'Error: Could not add teacher.';
                }
                $stmt->close();
            }
        }
    }

    $conn->close();
}

// Handle deleting teachers
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $conn = connectDB();
    $teacher_id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM teachers WHERE TeacherID = ?");
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
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE TeacherID = ?");
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
    <input type="hidden" name="teacher_id" value="<?php echo isset($teacher['TeacherID']) ? $teacher['TeacherID'] : ''; ?>">
    <label for="username">Username:</label>
    <input type="text" name="username" required value="<?php echo isset($teacher['Username']) ? htmlspecialchars($teacher['Username']) : ''; ?>"><br>
    <label for="password">Password:</label>
    <input type="password" name="password" required value="<?php echo isset($teacher['Password']) ? htmlspecialchars($teacher['Password']) : ''; ?>"><br>
    <label for="first_name">First Name:</label>
    <input type="text" name="first_name" required value="<?php echo isset($teacher['FirstName']) ? htmlspecialchars($teacher['FirstName']) : ''; ?>"><br>
    <label for="last_name">Last Name:</label>
    <input type="text" name="last_name" required value="<?php echo isset($teacher['LastName']) ? htmlspecialchars($teacher['LastName']) : ''; ?>"><br>
    <label for="email">Email:</label>
    <input type="email" name="email" required value="<?php echo isset($teacher['Email']) ? htmlspecialchars($teacher['Email']) : ''; ?>"><br>
    <label for="phone_number">Phone Number:</label>
    <input type="text" name="phone_number" required value="<?php echo isset($teacher['PhoneNumber']) ? htmlspecialchars($teacher['PhoneNumber']) : ''; ?>"><br>
    
    <input type="submit" name="<?php echo isset($teacher) ? 'update_teacher' : 'add_teacher'; ?>" value="<?php echo isset($teacher) ? 'Update Teacher' : 'Add Teacher'; ?>">
</form>

<!-- Table to display the list of teachers -->
<h2>Teachers List</h2>
<table>
    <tr>
        <th>Teacher ID</th>
        <th>Username</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Email</th>
        <th>Phone Number</th>
        <th>Actions</th>
    </tr>
    <?php
    // Fetch and display the list of teachers
    $conn = connectDB();  // Connect to the database
    $result = $conn->query("SELECT TeacherID, Username, FirstName, LastName, Email, PhoneNumber FROM teachers");

    // Loop through the results and display each teacher in a table row
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['TeacherID']) . '</td>';
            echo '<td>' . htmlspecialchars($row['Username']) . '</td>';
            echo '<td>' . htmlspecialchars($row['FirstName']) . '</td>';
            echo '<td>' . htmlspecialchars($row['LastName']) . '</td>';
            echo '<td>' . htmlspecialchars($row['Email']) . '</td>';
            echo '<td>' . htmlspecialchars($row['PhoneNumber']) . '</td>';
            echo '<td>
                    <a href="?action=edit&id=' . $row['TeacherID'] . '">Edit</a> | 
                    <a href="?action=delete&id=' . $row['TeacherID'] . '" onclick="return confirm(\'Are you sure you want to delete this teacher?\');">Delete</a>
                  </td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="7">No teachers found.</td></tr>';
    }

    // Close the database connection
    $conn->close();
    ?>
</table>

</body>
</html>

