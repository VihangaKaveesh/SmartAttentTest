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

// Function to fetch class data from the "classes" table for the course dropdown
function fetchClasses($conn) {
    $classes = [];  // Initialize an empty array to store classes
    $sql = "SELECT class_id, class_name FROM classes";  // SQL query to select class IDs and names
    $result = $conn->query($sql);  // Execute the query

    // Loop through the result and store each row in the array
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $classes[] = $row;
        }
    }
    return $classes;  // Return the array of classes
}

// Initialize the message array
$message = [];

// Handling form submission for adding a new student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_student"])) {
    // Sanitize user inputs
    $username = filter_var($_POST["username"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $password = $_POST["password"];  // Raw password input (will be hashed later)
    $name = filter_var($_POST["name"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $phoneno = filter_var($_POST["phone_number"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $course = isset($_POST["course"]) ? $_POST["course"] : '';  // Ensure $course is a string

    // Perform form validations
    if (!preg_match("/^[a-zA-Z]+$/", $name)) {
        $message[] = 'Name should contain only letters!';
    } elseif (!preg_match("/^[^\s@]+@[^\s@]+\.[^\s@]+$/", $email)) {
        $message[] = 'Please enter a valid email address!';
    } elseif (!preg_match("/^(?:\+?94)?[0-9]{9,10}$/", $phoneno)) {
        $message[] = 'Please enter a valid phone number (with +94)!';
    } else {
        $conn = connectDB();  // Connect to the database

        // Check if the username or email already exists in the "students" table
        $check_user_email = $conn->prepare("SELECT * FROM students WHERE username = ? OR email = ?");
        $check_user_email->bind_param("ss", $username, $email);
        $check_user_email->execute();
        $check_user_email->store_result();

        if ($check_user_email->num_rows > 0) {
            $message[] = 'Username or email already exists, please choose another!';
        } else {
            // Hash the password for security
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Prepare the SQL statement for inserting the new student
            $stmt = $conn->prepare("INSERT INTO students (username, password, name, email, phone_number, course) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $username, $hashed_password, $name, $email, $phoneno, $course);

            // Execute the query and provide feedback
            if ($stmt->execute()) {
                $message[] = 'Student added successfully.';
            } else {
                $message[] = 'Error adding student: ' . $stmt->error;
            }

            $stmt->close();  // Close the statement
        }
        $conn->close();  // Close the database connection
    }
}

// Handling form submission for updating an existing student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_student"])) {
    $studentId = $_POST["student_id"];  // Get the student ID from the hidden field
    // Sanitize user inputs
    $username = filter_var($_POST["username"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $password = $_POST["password"];  // Raw password input (will be hashed later)
    $name = filter_var($_POST["name"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $phoneno = filter_var($_POST["phone_number"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $course = isset($_POST["course"]) ? $_POST["course"] : '';  // Ensure $course is a string

    // Perform form validations
    if (!preg_match("/^[a-zA-Z]+$/", $name)) {
        $message[] = 'Name should contain only letters!';
    } elseif (!preg_match("/^[^\s@]+@[^\s@]+\.[^\s@]+$/", $email)) {
        $message[] = 'Please enter a valid email address!';
    } elseif (!preg_match("/^(?:\+?94)?[0-9]{9,10}$/", $phoneno)) {
        $message[] = 'Please enter a valid phone number (Sri Lankan format)!';
    } else {
        $conn = connectDB();  // Connect to the database

        // Check if the username or email already exists for another student
        $check_user_email = $conn->prepare("SELECT * FROM students WHERE (username = ? OR email = ?) AND student_id != ?");
        $check_user_email->bind_param("ssi", $username, $email, $studentId);
        $check_user_email->execute();
        $check_user_email->store_result();

        if ($check_user_email->num_rows > 0) {
            $message[] = 'Username or email already exists, please choose another!';
        } else {
            // Prepare the SQL statement for updating the student's data
            $stmt = $conn->prepare("UPDATE students SET username = ?, password = ?, name = ?, email = ?, phone_number = ?, course = ? WHERE student_id = ?");
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);  // Hash the password
            $stmt->bind_param("ssssssi", $username, $hashed_password, $name, $email, $phoneno, $course, $studentId);

            // Execute the query and provide feedback
            if ($stmt->execute()) {
                $message[] = 'Student updated successfully.';
            } else {
                $message[] = 'Error updating student: ' . $stmt->error;
            }

            $stmt->close();  // Close the statement
        }

        $conn->close();  // Close the database connection
    }
}

// Handling deletion of a student
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["action"]) && $_GET["action"] == "delete" && isset($_GET["id"])) {
    $studentId = $_GET["id"];  // Get the student ID from the URL
    $conn = connectDB();  // Connect to the database

    // Prepare the SQL statement for deleting the student
    $stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
    $stmt->bind_param("i", $studentId);

    // Execute the query and provide feedback
    if ($stmt->execute()) {
        $message[] = 'Student deleted successfully.';
    } else {
        $message[] = 'Error deleting student: ' . $stmt->error;
    }

    $stmt->close();  // Close the statement
    $conn->close();  // Close the database connection
}

// Fetch student data for editing (for the update form)
$student = null;
if (isset($_GET["action"]) && $_GET["action"] == "edit" && isset($_GET["id"])) {
    $studentId = $_GET["id"];  // Get the student ID from the URL
    $conn = connectDB();  // Connect to the database

    // Prepare the SQL statement to retrieve the student's data
    $stmt = $conn->prepare("SELECT student_id, username, password, name, email, phone_number, course FROM students WHERE student_id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();

    // If the student exists, fetch their data
    if ($result->num_rows == 1) {
        $student = $result->fetch_assoc();
    } else {
        $message[] = 'Student not found.';
    }

    $stmt->close();  // Close the statement
    $conn->close();  // Close the database connection
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>
    <style>
        /* Simple styles for the forms and table */
        form {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
    </style>
</head>
<body>
    <h1>Student Management</h1>

    <!-- Display messages -->
    <?php
    if (!empty($message)) {
        foreach ($message as $msg) {
            echo "<p>$msg</p>";
        }
    }
    ?>

    <!-- Add Student Form -->
    <form action="" method="post">
        <h2>Add New Student</h2>
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        <br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <br>
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>
        <br>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <br>
        <label for="phone_number">Phone Number:</label>
        <input type="text" id="phone_number" name="phone_number" required>
        <br>
        <label for="course">Course:</label>
        <select id="course" name="course" required>
            <?php
            // Fetch classes for the course dropdown
            $conn = connectDB();
            $classes = fetchClasses($conn);
            foreach ($classes as $class) {
                echo "<option value='" . htmlspecialchars($class['class_name']) . "'>" . htmlspecialchars($class['class_name']) . "</option>";
            }
            $conn->close();
            ?>
        </select>
        <br>
        <input type="submit" name="add_student" value="Add Student">
    </form>

    <!-- Update Student Form -->
    <?php if ($student): ?>
    <form action="" method="post">
        <h2>Update Student</h2>
        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student['student_id']); ?>">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($student['username']); ?>" required>
        <br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password">
        <br>
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" required>
        <br>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
        <br>
        <label for="phone_number">Phone Number:</label>
        <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($student['phone_number']); ?>" required>
        <br>
        <label for="course">Course:</label>
        <select id="course" name="course" required>
            <?php
            // Fetch classes for the course dropdown
            $conn = connectDB();
            $classes = fetchClasses($conn);
            foreach ($classes as $class) {
                echo "<option value='" . htmlspecialchars($class['class_name']) . "'" . ($class['class_name'] == $student['course'] ? ' selected' : '') . ">" . htmlspecialchars($class['class_name']) . "</option>";
            }
            $conn->close();
            ?>
        </select>
        <br>
        <input type="submit" name="update_student" value="Update Student">
    </form>
    <?php endif; ?>

    <!-- List of Students -->
    <h2>List of Students</h2>
    <?php
    $conn = connectDB();  // Connect to the database
    $sql = "SELECT student_id, username, name, email, phone_number, course FROM students";  // SQL query to fetch students
    $result = $conn->query($sql);  // Execute the query

    if ($result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Student ID</th><th>Username</th><th>Name</th><th>Email</th><th>Phone Number</th><th>Course</th><th>Actions</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['student_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['phone_number']) . "</td>";
            echo "<td>" . htmlspecialchars($row['course']) . "</td>";
            echo "<td><a href='?action=edit&id=" . htmlspecialchars($row['student_id']) . "'>Edit</a> | <a href='?action=delete&id=" . htmlspecialchars($row['student_id']) . "' onclick='return confirm(\"Are you sure you want to delete this student?\")'>Delete</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No students found.</p>";
    }

    $conn->close();  // Close the database connection
    ?>
</body>
</html>
