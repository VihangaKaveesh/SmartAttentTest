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

// Function to fetch class data from the "classes" table for the course checkboxes
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

// Handling form submission for adding a new teacher
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_teacher"])) {
    // Sanitize user inputs
    $username = filter_var($_POST["username"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $password = $_POST["password"];  // Raw password input (will be hashed later)
    $name = filter_var($_POST["name"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $phoneno = filter_var($_POST["phone_number"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $courses = isset($_POST["course"]) ? $_POST["course"] : [];  // Ensure $courses is an array

    // Perform form validations
    $message = [];  // Initialize an empty array to store messages

    if (!preg_match("/^[a-zA-Z]+$/", $name)) {
        $message[] = 'Name should contain only letters!';
    } elseif (!preg_match("/^[^\s@]+@[^\s@]+\.[^\s@]+$/", $email)) {
        $message[] = 'Please enter a valid email address!';
    } elseif (!preg_match("/^(?:\+?94)?[0-9]{9,10}$/", $phoneno)) {
        $message[] = 'Please enter a valid phone number (with +94)!';
    } else {
        $conn = connectDB();  // Connect to the database

        // Check if the username or email already exists in the "teachers" table
        $check_user = $conn->prepare("SELECT * FROM `teachers` WHERE username = ? OR email = ?");
        $check_user->bind_param("ss", $username, $email);
        $check_user->execute();
        $check_user->store_result();

        if ($check_user->num_rows > 0) {
            $message[] = 'Username or email already exists, please choose another!';
        } else {
            // Hash the password for security
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Prepare the SQL statement for inserting the new teacher
            $stmt = $conn->prepare("INSERT INTO teachers (username, password, name, email, phone_number) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $hashed_password, $name, $email, $phoneno);

            // Execute the query and provide feedback
            if ($stmt->execute()) {
                // Get the last inserted teacher ID
                $teacherId = $conn->insert_id;

                // Insert course data into the teacher_courses table
                if (!empty($courses)) {
                    foreach ($courses as $courseId) {
                        $stmt_courses = $conn->prepare("INSERT INTO teacher_courses (teacher_id, class_id) VALUES (?, ?)");
                        $stmt_courses->bind_param("ii", $teacherId, $courseId);
                        $stmt_courses->execute();
                        $stmt_courses->close();
                    }
                }

                echo "<p class='success'>Teacher added successfully.</p>";
            } else {
                echo "<p class='error'>Error adding teacher: " . $stmt->error . "</p>";
            }

            $stmt->close();  // Close the statement
        }
        $conn->close();  // Close the database connection
    }

    // Display error messages
    foreach ($message as $msg) {
        echo "<p class='error'>{$msg}</p>";
    }
}

// Function to fetch selected courses for a teacher
function fetchSelectedCourses($teacherId, $conn) {
    $selected_courses = [];  // Initialize an empty array to store selected courses
    $stmt_courses = $conn->prepare("SELECT class_id FROM teacher_courses WHERE teacher_id = ?");
    $stmt_courses->bind_param("i", $teacherId);
    $stmt_courses->execute();
    $result_courses = $stmt_courses->get_result();

    // Store the selected course IDs in the array
    if ($result_courses->num_rows > 0) {
        while ($row = $result_courses->fetch_assoc()) {
            $selected_courses[] = $row['class_id'];
        }
    }
    $stmt_courses->close();  // Close the statement

    return $selected_courses;  // Return the array of selected courses
}

// Handling form submission for updating an existing teacher
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_teacher"])) {
    $teacherId = $_POST["teacher_id"];  // Get the teacher ID from the hidden field
    // Sanitize user inputs
    $username = filter_var($_POST["username"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $password = $_POST["password"];  // Raw password input (will be hashed later)
    $name = filter_var($_POST["name"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $phoneno = filter_var($_POST["phone_number"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $courses = isset($_POST["course"]) ? $_POST["course"] : [];  // Ensure $courses is an array

    // Perform form validations
    $message = [];  // Initialize an empty array to store messages

    if (!preg_match("/^[a-zA-Z]+$/", $name)) {
        $message[] = 'Name should contain only letters!';
    } elseif (!preg_match("/^[^\s@]+@[^\s@]+\.[^\s@]+$/", $email)) {
        $message[] = 'Please enter a valid email address!';
    } elseif (!preg_match("/^(?:\+?94)?[0-9]{9,10}$/", $phoneno)) {
        $message[] = 'Please enter a valid phone number (Sri Lankan format)!';
    } else {
        $conn = connectDB();  // Connect to the database

        // Check if the username or email already exists in the "teachers" table
        $check_user = $conn->prepare("SELECT * FROM `teachers` WHERE (username = ? OR email = ?) AND teacher_id != ?");
        $check_user->bind_param("ssi", $username, $email, $teacherId);
        $check_user->execute();
        $check_user->store_result();

        if ($check_user->num_rows > 0) {
            $message[] = 'Username or email already exists, please choose another!';
        } else {
            // Prepare the SQL statement for updating the teacher's data
            $stmt = $conn->prepare("UPDATE teachers SET username = ?, password = ?, name = ?, email = ?, phone_number = ? WHERE teacher_id = ?");
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);  // Hash the password
            $stmt->bind_param("sssssi", $username, $hashed_password, $name, $email, $phoneno, $teacherId);

            // Execute the query and provide feedback
            if ($stmt->execute()) {
                // Delete existing course entries for the teacher
                $conn->query("DELETE FROM teacher_courses WHERE teacher_id = $teacherId");

                // Insert new course data into the teacher_courses table
                if (!empty($courses)) {
                    foreach ($courses as $courseId) {
                        $stmt_courses = $conn->prepare("INSERT INTO teacher_courses (teacher_id, class_id) VALUES (?, ?)");
                        $stmt_courses->bind_param("ii", $teacherId, $courseId);
                        $stmt_courses->execute();
                        $stmt_courses->close();
                    }
                }

                echo "<p class='success'>Teacher updated successfully.</p>";
            } else {
                echo "<p class='error'>Error updating teacher: " . $stmt->error . "</p>";
            }

            $stmt->close();  // Close the statement
        }
        $conn->close();  // Close the database connection
    }

    // Display error messages
    foreach ($message as $msg) {
        echo "<p class='error'>{$msg}</p>";
    }
}

// Handling deletion of a teacher
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["action"]) && $_GET["action"] == "delete" && isset($_GET["id"])) {
    $teacherId = $_GET["id"];  // Get the teacher ID from the URL
    $conn = connectDB();  // Connect to the database

    // Prepare the SQL statement for deleting the teacher
    $stmt = $conn->prepare("DELETE FROM teachers WHERE teacher_id = ?");
    $stmt->bind_param("i", $teacherId);

    // Execute the query and provide feedback
    if ($stmt->execute()) {
        // Delete associated courses
        $conn->query("DELETE FROM teacher_courses WHERE teacher_id = $teacherId");

        echo "<p class='success'>Teacher deleted successfully.</p>";
    } else {
        echo "<p class='error'>Error deleting teacher: " . $stmt->error . "</p>";
    }

    $stmt->close();  // Close the statement
    $conn->close();  // Close the database connection
}

// Fetch teacher data for editing (for the update form)
$teacher = null;
if (isset($_GET["action"]) && $_GET["action"] == "edit" && isset($_GET["id"])) {
    $teacherId = $_GET["id"];  // Get the teacher ID from the URL
    $conn = connectDB();  // Connect to the database

    // Prepare the SQL statement to retrieve the teacher's data
    $stmt = $conn->prepare("SELECT teacher_id, username, password, name, email, phone_number FROM teachers WHERE teacher_id = ?");
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();

    // If the teacher exists, fetch their data
    if ($result->num_rows == 1) {
        $teacher = $result->fetch_assoc();

        // Fetch the teacher's courses
        $stmt_courses = $conn->prepare("SELECT class_id FROM teacher_courses WHERE teacher_id = ?");
        $stmt_courses->bind_param("i", $teacherId);
        $stmt_courses->execute();
        $result_courses = $stmt_courses->get_result();

        // Store the selected course IDs for the teacher
        $selected_courses = [];
        if ($result_courses->num_rows > 0) {
            while ($row = $result_courses->fetch_assoc()) {
                $selected_courses[] = $row['class_id'];
            }
        }
        $stmt_courses->close();  // Close the courses statement
    }
    $stmt->close();  // Close the teacher statement
    $conn->close();  // Close the database connection
}
?>

<!-- Add Teacher Form -->
<form action="" method="post">
    <h2>Add New Teacher</h2>
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
    <label for="course">Courses:</label><br>
    <?php
    // Fetch classes from the database
    $conn = connectDB();
    $classes = fetchClasses($conn);
    $conn->close();
    ?>
    <?php foreach ($classes as $class): ?>
        <input type="checkbox" name="course[]" value="<?php echo htmlspecialchars($class['class_id']); ?>">
        <?php echo htmlspecialchars($class['class_name']); ?><br>
    <?php endforeach; ?>
    <br>
    <input type="submit" name="add_teacher" value="Add Teacher">
</form>

<!-- Update Teacher Form -->
<?php if ($teacher): ?>
<form action="" method="post">
    <h2>Update Teacher</h2>
    <input type="hidden" name="teacher_id" value="<?php echo htmlspecialchars($teacher['teacher_id']); ?>">
    <label for="username">Username:</label>
    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($teacher['username']); ?>" required>
    <br>
    <label for="password">Password:</label>
    <input type="password" id="password" name="password">
    <br>
    <label for="name">Name:</label>
    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($teacher['name']); ?>" required>
    <br>
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($teacher['email']); ?>" required>
    <br>
    <label for="phone_number">Phone Number:</label>
    <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($teacher['phone_number']); ?>" required>
    <br>
    <label for="course">Courses:</label><br>
    <?php
    // Fetch classes from the database
    $conn = connectDB();
    $classes = fetchClasses($conn);
    $selected_courses = fetchSelectedCourses($teacher['teacher_id'], $conn); // Function to fetch selected courses for the teacher
    $conn->close();
    ?>
    <?php foreach ($classes as $class): ?>
        <input type="checkbox" name="course[]" value="<?php echo htmlspecialchars($class['class_id']); ?>"
            <?php echo in_array($class['class_id'], $selected_courses) ? 'checked' : ''; ?>>
        <?php echo htmlspecialchars($class['class_name']); ?><br>
    <?php endforeach; ?>
    <br>
    <input type="submit" name="update_teacher" value="Update Teacher">
</form>
<?php endif; ?>

<!-- List of Teachers -->
<h2>List of Teachers</h2>
<?php
// Fetch existing teachers from the database
$conn = connectDB();
$sql = "SELECT * FROM teachers";
$result = $conn->query($sql);
?>
<table border="1">
    <tr>
        <th>Teacher ID</th>
        <th>Username</th>
        <th>Name</th>
        <th>Email</th>
        <th>Phone Number</th>
        <th>Courses</th>
        <th>Actions</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
        <?php
        // Fetch courses for this teacher
        $teacherId = $row['teacher_id'];
        $stmt_courses = $conn->prepare("SELECT c.class_name FROM teacher_courses tc JOIN classes c ON tc.class_id = c.class_id WHERE tc.teacher_id = ?");
        $stmt_courses->bind_param("i", $teacherId);
        $stmt_courses->execute();
        $result_courses = $stmt_courses->get_result();
        $courses = [];
        while ($course_row = $result_courses->fetch_assoc()) {
            $courses[] = $course_row['class_name'];
        }
        $stmt_courses->close();
        ?>
        <tr>
            <td><?php echo htmlspecialchars($row['teacher_id']); ?></td>
            <td><?php echo htmlspecialchars($row['username']); ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
            <td><?php echo htmlspecialchars(implode(', ', $courses)); ?></td>
            <td>
                <a href="?action=edit&id=<?php echo htmlspecialchars($row['teacher_id']); ?>">Edit</a> |
                <a href="?action=delete&id=<?php echo htmlspecialchars($row['teacher_id']); ?>" onclick="return confirm('Are you sure you want to delete this teacher?');">Delete</a>
            </td>
        </tr>
    <?php endwhile; ?>
</table>
<?php $conn->close(); ?>

