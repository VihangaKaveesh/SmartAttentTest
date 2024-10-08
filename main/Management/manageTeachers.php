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

// If logged in as management, the rest of your manage teachers form can go here

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <style>
     
     body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f3f3;
            margin: 0;
            padding: 0;
        }

        h1 {
            color: #26648E;
            text-align: center;
            margin-top: 20px;
        }

        h2 {
            color: #6A1B9A;
            text-align: center;
        }

        form {
            width: 60%;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        form label {
            font-weight: bold;
            display: block;
            margin-bottom: 10px;
            color: #26648E;
        }

        form input[type="text"], form input[type="password"], form input[type="email"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 2px solid #d1d1d1;
            border-radius: 5px;
            transition: border-color 0.3s ease;
        }

        form input[type="text"]:focus, form input[type="password"]:focus, form input[type="email"]:focus {
            border-color: #6A1B9A;
        }

        form input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #6A1B9A;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        form input[type="submit"]:hover {
            background-color: #8E24AA;
        }

        table {
            width: 90%;
            margin: 30px auto;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        table th, table td {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background-color: #26648E;
            color: white;
            font-weight: bold;
        }

        table tr:nth-child(even) {
            background-color: #f3f3f3;
        }

        table tr:hover {
            background-color: #e0e0e0;
        }

        table a {
            color: #6A1B9A;
            text-decoration: none;
            font-weight: bold;
        }

        table a:hover {
            text-decoration: underline;
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
        <a href="../login/login.html">Logout</a>
    </div>
</div>

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
<h2 style="color: #26648E;">Teacher List</h2>
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
    $result = $conn->query("SELECT * FROM teachers");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Username']) . "</td>";
            echo "<td>" . htmlspecialchars($row['FirstName']) . "</td>";
            echo "<td>" . htmlspecialchars($row['LastName']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['PhoneNumber']) . "</td>";
            echo "<td><a href='?action=edit&id=" . $row['TeacherID'] . "'>Edit</a> | <a href='?action=delete&id=" . $row['TeacherID'] . "' onclick='return confirm(\"Are you sure you want to delete this teacher?\")'>Delete</a></td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='6'>No teachers found.</td></tr>";
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