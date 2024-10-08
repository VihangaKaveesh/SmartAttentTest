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

// Function to check if a module name already exists
function isModuleNameExists($module_name, $module_id = null) {
    $conn = connectDB();
    $query = "SELECT ModuleID FROM modules WHERE ModuleName = ?";
    
    // If updating, exclude the current module by ID
    if ($module_id !== null) {
        $query .= " AND ModuleID != ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $module_name, $module_id);
    } else {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $module_name);
    }

    $stmt->execute();
    $stmt->store_result();

    $exists = $stmt->num_rows > 0;
    $stmt->close();
    $conn->close();
    return $exists;
}

// Fetch teachers from the database for the dropdown, combine FirstName and LastName
function getTeachers() {
    $conn = connectDB();
    $query = "SELECT TeacherID, FirstName, LastName FROM teachers";
    $result = $conn->query($query);
    $teachers = [];

    while ($row = $result->fetch_assoc()) {
        $full_name = $row['FirstName'] . ' ' . $row['LastName'];
        $teachers[] = ['TeacherID' => $row['TeacherID'], 'FullName' => $full_name];
    }

    $conn->close();
    return $teachers;
}

// Initialize module variables
$module_name = "";
$module_id = null;
$teacher_id = null;

// Handle adding a module
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_module"])) {
    $module_name = filter_var($_POST["ModuleName"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $teacher_id = $_POST["TeacherID"]; // Get the selected teacher ID

    if (!empty($module_name) && !empty($teacher_id)) {
        if (isModuleNameExists($module_name)) {
            echo "<p class='error'>Module name already exists. Please choose a different name.</p>";
        } else {
            $conn = connectDB();
            $stmt = $conn->prepare("INSERT INTO modules (ModuleName, TeacherID) VALUES (?, ?)");
            $stmt->bind_param("si", $module_name, $teacher_id);

            if ($stmt->execute()) {
                echo "<p class='success'>Module added successfully.</p>";
                $module_name = ""; // Clear the module name after addition
            } else {
                echo "<p class='error'>Error adding module: " . $stmt->error . "</p>";
            }

            $stmt->close();
            $conn->close();
        }
    } else {
        echo "<p class='error'>Module name and teacher selection cannot be empty.</p>";
    }
}

// Handle updating a module
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_module"])) {
    $module_id = $_POST["ModuleID"];
    $module_name = filter_var($_POST["ModuleName"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $teacher_id = $_POST["TeacherID"];

    if (!empty($module_name) && !empty($teacher_id)) {
        if (isModuleNameExists($module_name, $module_id)) {
            echo "<p class='error'>Module name already exists. Please choose a different name.</p>";
        } else {
            $conn = connectDB();
            $stmt = $conn->prepare("UPDATE modules SET ModuleName = ?, TeacherID = ? WHERE ModuleID = ?");
            $stmt->bind_param("sii", $module_name, $teacher_id, $module_id);

            if ($stmt->execute()) {
                echo "<p class='success'>Module updated successfully.</p>";
                header("Location: addModules.php"); // Redirect to reset the form to "Add Module" mode
                exit(); // Exit after redirection
            } else {
                echo "<p class='error'>Error updating module: " . $stmt->error . "</p>";
            }

            $stmt->close();
            $conn->close();
        }
    } else {
        echo "<p class='error'>Module name and teacher selection cannot be empty.</p>";
    }
}

// Handle deleting a module
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $module_id = $_GET['id'];
    
    $conn = connectDB();
    $stmt = $conn->prepare("DELETE FROM modules WHERE ModuleID = ?");
    $stmt->bind_param("i", $module_id);
    
    if ($stmt->execute()) {
        echo "<p class='success'>Module deleted successfully.</p>";
    } else {
        echo "<p class='error'>Error deleting module: " . $stmt->error . "</p>";
    }

    $stmt->close();
    $conn->close();
}

// Fetch the module if in edit mode
$module_to_edit = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $module_id = $_GET['id'];
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT * FROM modules WHERE ModuleID = ?");
    $stmt->bind_param("i", $module_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $module_to_edit = $result->fetch_assoc();
        // Set the module name to the value fetched from the database for editing
        $module_name = $module_to_edit['ModuleName'];
        $teacher_id = $module_to_edit['TeacherID'];
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

.error {
    text-align: center;
    background-color: #f2dede;
    color: #a94442;
    padding: 10px;
    margin: 20px auto;
    border-radius: 5px;
    width: 80%;
}

.success {
    text-align: center;
    background-color: #dff0d8;
    color: #3c763d;
    padding: 10px;
    margin: 20px auto;
    border-radius: 5px;
    width: 80%;
}

/* Form and Table Styles */
form {
    max-width: 500px;
    margin: 20px auto;
    padding: 20px;
    background-color: #fff;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
    background-color: #a03aba;
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
<h1>Manage Modules</h1>

<!-- Form to add or update a module -->
<form action="" method="post">
    <label for="ModuleName">Module Name:</label>
    <input type="text" id="ModuleName" name="ModuleName" required value="<?php echo htmlspecialchars($module_name); ?>">

    <label for="TeacherID">Assign Teacher:</label>
    <select id="TeacherID" name="TeacherID" required>
        <option value="">Select a teacher</option>
        <?php
        // Populate the dropdown with full teacher names (FirstName LastName)
        $teachers = getTeachers();
        foreach ($teachers as $teacher) {
            $selected = ($teacher_id == $teacher['TeacherID']) ? 'selected' : '';
            echo "<option value='{$teacher['TeacherID']}' $selected>{$teacher['FullName']}</option>";
        }
        ?>
    </select>

    <?php if ($module_to_edit): ?>
        <input type="hidden" name="ModuleID" value="<?php echo htmlspecialchars($module_to_edit['ModuleID']); ?>">
        <input type="submit" name="update_module" value="Update Module">
    <?php else: ?>
        <input type="submit" name="add_module" value="Add Module">
    <?php endif; ?>
</form>

<!-- Display modules table -->
<table>
    <thead>
        <tr>
            <th>Module Name</th>
            <th>Teacher</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $conn = connectDB();
        $result = $conn->query("SELECT modules.ModuleID, modules.ModuleName, CONCAT(teachers.FirstName, ' ', teachers.LastName) AS TeacherName FROM modules LEFT JOIN teachers ON modules.TeacherID = teachers.TeacherID");

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['ModuleName']) . '</td>';
                echo '<td>' . htmlspecialchars($row['TeacherName']) . '</td>';
                echo '<td>
                    <a href="?action=edit&id=' . $row['ModuleID'] . '">Edit</a> | 
                    <a href="?action=delete&id=' . $row['ModuleID'] . '" onclick="return confirm(\'Are you sure you want to delete this module?\')">Delete</a> | 
                    <a href="marksAnalysis.php?module_id=' . $row['ModuleID'] . '">View Marks Analysis</a> |
                    <a href="bulkEmail.php?module_id=' . $row['ModuleID'] . '">General Notice</a>
                </td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="3">No modules found.</td></tr>';
        }

        $conn->close();
        ?>
    </tbody>
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
