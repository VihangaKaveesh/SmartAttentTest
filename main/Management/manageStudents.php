<?php
session_start();

// Check if the user is logged in as management
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'management') {
    // If not logged in or not management, redirect to the login page
    header("Location: ../login/login.php");
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

    // Validation patterns
    $name_regex = "/^[A-Za-z]+$/"; // Only letters
    $phone_regex = "/^(?:\+94)?[0-9]{9,10}$/"; // Sri Lankan phone numbers with optional +94 prefix

    // Validate required fields
    if (empty($username) || empty($password) || empty($first_name) || empty($last_name) || empty($email) || empty($phone_number) || empty($module_id)) {
        $message[] = 'Error: All fields are required.';
    } elseif (!preg_match($name_regex, $first_name)) {
        $message[] = 'Error: First name should contain only letters.';
    } elseif (!preg_match($name_regex, $last_name)) {
        $message[] = 'Error: Last name should contain only letters.';
    } elseif (!preg_match($phone_regex, $phone_number)) {
        $message[] = 'Error: Phone number is invalid. It should have 9-10 digits or start with +94.';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        /* General Styling */
        h1 {
            text-align: center;
            color: #5a4dcf;
            margin: 30px 0;
        }

        form {
            max-width: 500px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 10px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        input[type="submit"] {
            background-color: #5a4dcf;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: #4a3db0;
        }

        /* Table Styling */
        table {
            width: 80%;
            margin: 20px auto;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #5a4dcf;
            color: white;
        }

        td {
            color: #333;
        }

        td a {
            text-decoration: none;
            color: #5a4dcf;
            font-weight: bold;
        }

        td a:hover {
            text-decoration: underline;
        }

        .message {
            text-align: center;
            background-color: #dff0d8;
            color: #3c763d;
            padding: 10px;
            margin: 20px auto;
            border-radius: 5px;
            width: 80%;
        }

        .error {
            text-align: center;
            background-color: #f2dede;
            color: #a94442;
            padding: 10px;
            margin: 20px auto;
            border-radius: 5px;
            width: 80%;
        }

         /* Hamburger Menu Icon */
         .hamburger {
            font-size: 2rem;
            cursor: pointer;
            margin: 10px;
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 2000;
        }

        /* Sidebar Styling */
        .sidebar {
            position: fixed;
            top: 0;
            left: -100%;
            height: 100%;
            width: 100vw;
            background-color:#a03aba;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            transition: left 0.4s ease;
            z-index: 1500;
        }

        .sidebar.active {
            left: 0;
        }

        .nav-links a {
            color: white;
            padding: 20px;
            margin: 10px 0;
            text-decoration: none;
            font-weight: 500;
            font-size: 1.5rem;
            font-family: 'Poppins', sans-serif;
            text-align: center;
            width: 100%;
            transition: background 0.3s, padding 0.3s, transform 0.3s ease;
            position: relative;
        }

        /* Modern Hover Animation */
        .nav-links a::before {
            content: '';
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            height: 3px;
            background: #fff;
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.3s ease;
        }

        .nav-links a:hover::before {
            transform: scaleX(1);
            transform-origin: left;
        }

        .nav-links a:hover {
            background-color: #d448f7;
            border-radius: 5px;
            transform: translateY(-5px);
        }
        /* Responsive Styling */
        @media (max-width: 768px) {
            table, form {
                width: 100%;
                margin: 0;
            }

            table th, table td {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
 <!-- Hamburger Icon -->
<div class="hamburger">
    <i class="fas fa-bars"></i>
</div>

<!-- Sidebar Menu -->
<div class="sidebar">
    <div class="nav-links">
        <a href="manageStudents.php">Students</a><br><br><br><br><br>
        <a href="addModules.php">Modules</a><br><br><br><br><br>
        <a href="manageTeachers.php">Teachers</a><br><br><br><br><br>
        <a href="notice.php">Notices</a><br><br><br><br><br>
        <a href="addLabs.php">Labs</a><br><br><br><br><br>
        <a href="../login/login.php">Logout</a>
    </div>
</div>

<h1>Manage Students</h1>

<!-- Display messages -->
<?php if (!empty($message)) : ?>
    <div class="<?php echo strpos(implode($message), 'Error') !== false ? 'error' : 'message'; ?>">
        <?php echo implode('<br>', $message); ?>
    </div>
<?php endif; ?>

<!-- Student Form -->
<form action="manageStudents.php" method="POST" onsubmit="return validate()">
    <input type="hidden" name="student_id" value="<?php echo isset($student['StudentID']) ? $student['StudentID'] : ''; ?>">

    <label for="username">Username:</label>
    <input type="text" id="username" name="username" value="<?php echo isset($student['Username']) ? $student['Username'] : ''; ?>" required>

    <label for="first_name">First Name:</label>
    <input type="text" id="first_name" name="first_name" value="<?php echo isset($student['FirstName']) ? $student['FirstName'] : ''; ?>" required>

    <label for="last_name">Last Name:</label>
    <input type="text" id="last_name" name="last_name" value="<?php echo isset($student['LastName']) ? $student['LastName'] : ''; ?>" required>

    <label for="password">Password:</label>
    <input type="password" id="password" name="password" value="<?php echo isset($student['Password']) ? $student['Password'] : ''; ?>" required>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" value="<?php echo isset($student['Email']) ? $student['Email'] : ''; ?>" required>

    <label for="phone_number">Phone Number:</label>
    <input type="text" id="phone_number" name="phone_number" value="<?php echo isset($student['PhoneNumber']) ? $student['PhoneNumber'] : ''; ?>" required>

    <label for="course">Select Module:</label>
    <select id="course" name="course" required>
        <option value="">--Select Module--</option>
        <?php
        $conn = connectDB();
        $modules = fetchModules($conn);
        foreach ($modules as $module) {
            $selected = isset($student['ModuleID']) && $student['ModuleID'] == $module['ModuleID'] ? 'selected' : '';
            echo "<option value='" . $module['ModuleID'] . "' $selected>" . $module['ModuleName'] . "</option>";
        }
        $conn->close();
        ?>
    </select>

    <input type="submit" name="<?php echo isset($student['StudentID']) ? 'update_student' : 'add_student'; ?>" value="<?php echo isset($student['StudentID']) ? 'Update Student' : 'Add Student'; ?>">
</form>

<!-- Display existing students -->
<center><h2 style="color: #26648E;">Student List</h2></center>
<table>
    <tr>
        <th>Username</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Email</th>
        <th>Phone Number</th>
        <th>Actions</th>
    </tr>
    <?php
    // Fetch all teachers from the database and display them in the table
    $conn = connectDB();
    $result = $conn->query("SELECT * FROM students");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Username']) . "</td>";
            echo "<td>" . htmlspecialchars($row['FirstName']) . "</td>";
            echo "<td>" . htmlspecialchars($row['LastName']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['PhoneNumber']) . "</td>";
            echo '<td>
            <a href="?action=edit&id=' . $row['StudentID'] . '">Edit</a> | 
            <a href="?action=delete&id=' . $row['StudentID'] . '" onclick="return confirm(\'Are you sure you want to delete this student?\')">Delete</a> | 
            <a href="attendanceAnalysis.php?student_id=' . $row['StudentID'] . '">View Attendance</a>
        </td>';
        echo '</tr>';
        }
    } else {
        echo "<tr><td colspan='6'>No students found.</td></tr>";
    }
    $conn->close();
    ?>
</table>
<script>
    // Toggle Sidebar
    const hamburger = document.querySelector('.hamburger');
    const sidebar = document.querySelector('.sidebar');

    hamburger.addEventListener('click', function() {
        sidebar.classList.toggle('active');
    });
</script>
</body>
</html>