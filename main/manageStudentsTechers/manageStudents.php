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

// Handling form submission for adding a new student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_student"])) {
    // Sanitize user inputs
    $username = filter_var($_POST["username"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $password = $_POST["password"];  // Raw password input (will be hashed later)
    $name = filter_var($_POST["name"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $phoneno = filter_var($_POST["phone_number"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $course = $_POST["course"];  // Course ID selected from the dropdown

    // Perform form validations
    if (!preg_match("/^[a-zA-Z]+$/", $name)) {
        $message[] = 'Name should contain only letters!';
    } elseif (!preg_match("/^[^\s@]+@[^\s@]+\.[^\s@]+$/", $email)) {
        $message[] = 'Please enter a valid email address!';
    } elseif (!preg_match("/^(?:\+?94)?[0-9]{9,10}$/", $phoneno)) {
        $message[] = 'Please enter a valid phone number (Sri Lankan format)!';
    } else {
        $conn = connectDB();  // Connect to the database

        // Check if the username already exists in the "students" table
        $check_user = $conn->prepare("SELECT * FROM `students` WHERE username = ?");
        $check_user->bind_param("s", $username);
        $check_user->execute();
        $check_user->store_result();

        if ($check_user->num_rows > 0) {
            $message[] = 'Username already exists, please choose another!';
        } else {
            // Hash the password for security
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Prepare the SQL statement for inserting the new student
            $stmt = $conn->prepare("INSERT INTO students (username, password, name, email, phone_number, course) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssi", $username, $hashed_password, $name, $email, $phoneno, $course);

            // Execute the query and provide feedback
            if ($stmt->execute()) {
                echo "<p class='success'>Student added successfully.</p>";
            } else {
                echo "<p class='error'>Error adding student: " . $stmt->error . "</p>";
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
    $course = $_POST["course"];  // Course ID selected from the dropdown

    // Perform form validations
    if (!preg_match("/^[a-zA-Z]+$/", $name)) {
        $message[] = 'Name should contain only letters!';
    } elseif (!preg_match("/^[^\s@]+@[^\s@]+\.[^\s@]+$/", $email)) {
        $message[] = 'Please enter a valid email address!';
    } elseif (!preg_match("/^(?:\+?94)?[0-9]{9,10}$/", $phoneno)) {
        $message[] = 'Please enter a valid phone number (Sri Lankan format)!';
    } else {
        $conn = connectDB();  // Connect to the database

        // Prepare the SQL statement for updating the student's data
        $stmt = $conn->prepare("UPDATE students SET username = ?, password = ?, name = ?, email = ?, phone_number = ?, course = ? WHERE student_id = ?");
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);  // Hash the password
        $stmt->bind_param("ssssiii", $username, $hashed_password, $name, $email, $phoneno, $course, $studentId);

        // Execute the query and provide feedback
        if ($stmt->execute()) {
            echo "<p class='success'>Student updated successfully.</p>";
        } else {
            echo "<p class='error'>Error updating student: " . $stmt->error . "</p>";
        }

        $stmt->close();  // Close the statement
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
        echo "<p class='success'>Student deleted successfully.</p>";
    } else {
        echo "<p class='error'>Error deleting student: " . $stmt->error . "</p>";
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
        echo "<p class='error'>Student not found.</p>";
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
    <title>Student Management</title>
</head>
<body>
    <div class="container">
        <h1>Student Management</h1>

        <!-- Form for adding a new student -->
        <h2>Add New Student</h2>
        <form action="manageStudents.php" method="post">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="phone_number">Contact No:</label>
            <input type="text" id="phone_number" name="phone_number" required>

            <label for="course">Course:</label>
            <select id="course" name="course" required>
                <?php
                $conn = connectDB();  // Connect to the database
                $classes = fetchClasses($conn);  // Fetch class data from the "classes" table
                foreach ($classes as $class) {
                    echo '<option value="' . $class['class_id'] . '">' . htmlspecialchars($class['class_name'], ENT_QUOTES, 'UTF-8') . '</option>';
                }
                $conn->close();  // Close the database connection
                ?>
            </select>

            <button type="submit" name="add_student">Add Student</button>
        </form>

        <!-- Form for updating an existing student -->
        <?php if ($student): ?>
        <h2>Edit Student</h2>
        <form action="manageStudents.php" method="post">
            <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">

            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($student['username'], ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" value="" required>

            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($student['email'], ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="phone_number">Contact No:</label>
            <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($student['phone_number'], ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="course">Course:</label>
            <select id="course" name="course" required>
                <?php
                $conn = connectDB();  // Connect to the database
                $classes = fetchClasses($conn);  // Fetch class data from the "classes" table
                foreach ($classes as $class) {
                    echo '<option value="' . $class['class_id'] . '" ' . ($class['class_id'] == $student['course'] ? 'selected' : '') . '>' . htmlspecialchars($class['class_name'], ENT_QUOTES, 'UTF-8') . '</option>';
                }
                $conn->close();  // Close the database connection
                ?>
            </select>

            <button type="submit" name="update_student">Update Student</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
