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
    $courses = isset($_POST["courses"]) ? $_POST["courses"] : [];  // Ensure $courses is an array

    // Perform form validations
    if (!preg_match("/^[a-zA-Z]+$/", $name)) {
        $message[] = 'Name should contain only letters!';
    } elseif (!preg_match("/^[^\s@]+@[^\s@]+\.[^\s@]+$/", $email)) {
        $message[] = 'Please enter a valid email address!';
    } elseif (!preg_match("/^(?:\+?94)?[0-9]{9,10}$/", $phoneno)) {
        $message[] = 'Please enter a valid phone number (Sri Lankan format)!';
    } else {
        $conn = connectDB();  // Connect to the database

        // Check if the username already exists in the "teachers" table
        $check_user = $conn->prepare("SELECT * FROM `teachers` WHERE username = ?");
        $check_user->bind_param("s", $username);
        $check_user->execute();
        $check_user->store_result();

        if ($check_user->num_rows > 0) {
            $message[] = 'Username already exists, please choose another!';
        } else {
            // Hash the password for security
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Prepare the SQL statement for inserting the new teacher
            $stmt = $conn->prepare("INSERT INTO teachers (username, password, name, email, phone_number) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $hashed_password, $name, $email, $phoneno);

            // Execute the query and provide feedback
            if ($stmt->execute()) {
                // Get the inserted teacher_id
                $teacher_id = $stmt->insert_id;

                // Insert course associations
                if (!empty($courses)) {
                    $stmt = $conn->prepare("INSERT INTO classes (teacher_id, class_id) VALUES (?, ?)");
                    foreach ($courses as $course) {
                        $stmt->bind_param("ii", $teacher_id, $course);
                        $stmt->execute();
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
    $courses = isset($_POST["courses"]) ? $_POST["courses"] : [];  // Ensure $courses is an array

    // Perform form validations
    if (!preg_match("/^[a-zA-Z]+$/", $name)) {
        $message[] = 'Name should contain only letters!';
    } elseif (!preg_match("/^[^\s@]+@[^\s@]+\.[^\s@]+$/", $email)) {
        $message[] = 'Please enter a valid email address!';
    } elseif (!preg_match("/^(?:\+?94)?[0-9]{9,10}$/", $phoneno)) {
        $message[] = 'Please enter a valid phone number (Sri Lankan format)!';
    } else {
        $conn = connectDB();  // Connect to the database

        // Prepare the SQL statement for updating the teacher's data
        $stmt = $conn->prepare("UPDATE teachers SET username = ?, password = ?, name = ?, email = ?, phone_number = ? WHERE teacher_id = ?");
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);  // Hash the password
        $stmt->bind_param("ssssii", $username, $hashed_password, $name, $email, $phoneno, $teacherId);

        // Execute the query and provide feedback
        if ($stmt->execute()) {
            // Delete existing course associations
            $stmt = $conn->prepare("DELETE FROM classes WHERE teacher_id = ?");
            $stmt->bind_param("i", $teacherId);
            $stmt->execute();

            // Insert updated course associations
            if (!empty($courses)) {
                $stmt = $conn->prepare("INSERT INTO classes (teacher_id, class_id) VALUES (?, ?)");
                foreach ($courses as $course) {
                    $stmt->bind_param("ii", $teacherId, $course);
                    $stmt->execute();
                }
            }
            echo "<p class='success'>Teacher updated successfully.</p>";
        } else {
            echo "<p class='error'>Error updating teacher: " . $stmt->error . "</p>";
        }

        $stmt->close();  // Close the statement
        $conn->close();  // Close the database connection
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
    } else {
        echo "<p class='error'>Teacher not found.</p>";
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
    <title>Manage Teachers</title>
    <style>
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Manage Teachers</h1>

   <!-- Form for adding a new teacher -->
<form action="manageTeachers.php" method="POST">
    <input type="hidden" name="add_teacher">
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
    <label for="courses">Courses:</label>
    <select id="courses" name="courses[]" multiple>
        <?php
            $conn = connectDB();
            $classes = fetchClasses($conn);
            foreach ($classes as $class):
                $selected = '';
                if (isset($teacher)) {
                    $stmt = $conn->prepare("SELECT * FROM classes WHERE teacher_id = ? AND class_id = ?");
                    $stmt->bind_param("ii", $teacher['teacher_id'], $class['class_id']);
                    $stmt->execute();
                    if ($stmt->get_result()->num_rows > 0) {
                        $selected = 'selected';
                    }
                    $stmt->close();
                }
        ?>
            <option value="<?php echo $class['class_id']; ?>" <?php echo $selected; ?>>
                <?php echo htmlspecialchars($class['class_name']); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <br>
    <input type="submit" value="Add Teacher">
</form>

<!-- Form for updating an existing teacher -->
<?php if ($teacher): ?>
    <form action="manageTeachers.php" method="POST">
        <input type="hidden" name="update_teacher">
        <input type="hidden" name="teacher_id" value="<?php echo $teacher['teacher_id']; ?>">
        <h2>Update Teacher</h2>
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
        <label for="courses">Courses:</label>
        <select id="courses" name="courses[]" multiple>
            <?php
                $conn = connectDB();
                $classes = fetchClasses($conn);
                foreach ($classes as $class):
                    $selected = '';
                    $stmt = $conn->prepare("SELECT * FROM classes WHERE teacher_id = ? AND class_id = ?");
                    $stmt->bind_param("ii", $teacher['teacher_id'], $class['class_id']);
                    $stmt->execute();
                    if ($stmt->get_result()->num_rows > 0) {
                        $selected = 'selected';
                    }
                    $stmt->close();
            ?>
                <option value="<?php echo $class['class_id']; ?>" <?php echo $selected; ?>>
                    <?php echo htmlspecialchars($class['class_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br>
        <input type="submit" value="Update Teacher">
    </form>
<?php endif; ?>

    

    <!-- Display messages -->
    <?php if (isset($message) && !empty($message)): ?>
        <div>
            <?php foreach ($message as $msg): ?>
                <p class="error"><?php echo htmlspecialchars($msg); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Display teachers with edit/delete options -->
<h2>Existing Teachers</h2>
<?php
    $conn = connectDB();
    // Update SQL query to include relevant columns from the 'teachers' table
    $sql = "SELECT teacher_id, username, name, email, phone_number FROM teachers";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<table border='1'>
                <tr>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone Number</th>
                    <th>Actions</th>
                </tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['phone_number']) . "</td>";
            echo "<td>
                    <a href='manageTeachers.php?action=edit&id=" . $row['teacher_id'] . "'>Edit</a> | 
                    <a href='manageTeachers.php?action=delete&id=" . $row['teacher_id'] . "' onclick=\"return confirm('Are you sure you want to delete this teacher?');\">Delete</a>
                  </td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No teachers found.</p>";
    }

    $conn->close();  // Close the database connection
?>

</body>
</html>
