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
    <style>
       body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #FFE3B3 0%, #53D2DC 100%);
            color: #26648E;
            margin: 0;
            padding: 20px;
        }

        h1 {
            text-align: center;
            font-size: 2em;
            margin-bottom: 30px;
        }

        form {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }

        label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }

        input[type="text"],
        input[type="email"],
        select {
            width: 100%;
            padding: 10px;
            margin: 5px 0 15px 0;
            border: 1px solid #4F8FC0;
            border-radius: 5px;
            background-color: #FFE3B3;
            color: #26648E;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        select:focus {
            border-color: #26648E;
            outline: none;
        }

        input[type="submit"] {
            background-color: #26648E;
            color: #FFE3B3;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
            width: 100%; /* Make buttons full width */
        }

        input[type="submit"]:hover {
            background-color: #4F8FC0;
            transform: translateY(-2px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #4F8FC0;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #26648E;
            color: #FFE3B3;
        }

        td {
            background-color: rgba(255, 255, 255, 0.8);
        }

        .success {
            color: green;
            text-align: center;
            margin: 10px 0;
        }

        .error {
            color: red;
            text-align: center;
            margin: 10px 0;
        }

        /* Responsive Design */
        @media (max-width: 600px) {
            h1 {
                font-size: 1.5em;
            }

            input[type="submit"] {
                padding: 10px;
            }

            table {
                font-size: 14px;
            }
        }

        /* Navigation Bar Styles */
        .navbar {
            display: flex;
            flex-wrap: wrap; /* Allow wrapping for responsiveness */
            justify-content: space-between;
            align-items: center;
            background-color: #333;
            padding: 1rem;
            position: relative; /* For positioning the dropdown */
        }

        .logo {
            color: white;
            font-size: 1.5rem;
            flex: 1; /* Allow logo to take available space */
        }

        .nav-links {
            list-style: none;
            display: flex;
            margin: 0;
            padding: 0;
            flex: 2; /* Allow nav links to take available space */
            justify-content: flex-end; /* Align to the right */
        }

        .nav-links li {
            margin-left: 1rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 12px; /* Add padding for better click area */
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .nav-links a:hover {
            background-color: #ff4081; /* Change to your preferred hover color */
        }

        /* Hamburger Menu Styles */
        .hamburger {
            display: none;
            flex-direction: column;
            cursor: pointer;
        }

        .hamburger .line {
            height: 3px;
            width: 25px;
            background-color: white;
            margin: 3px 0;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
                flex-direction: column;
                width: 100%;
                position: absolute;
                top: 60px; /* Adjust based on navbar height */
                left: 0;
                background-color: #333;
                z-index: 10;
            }

            .nav-links.active {
                display: flex;
            }

            .hamburger {
                display: flex;
            }
        }

        /* Additional Styles for Page Content */
        .container {
            padding: 20px;
        }

        /* Responsive Table */
        @media (max-width: 768px) {
            table {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
<header>
    <nav class="navbar">
        <div class="logo">Your Logo</div>
        <ul class="nav-links">
            <li><a href="dashboard.html">Dashboard</a></li>
            <li><a href="manageStudents.php">Students</a></li>
            <li><a href="manageTeachers.php">Teachers</a></li>
            <li><a href="addModules.php">Modules</a></li>
            <li><a href="logout.html">Logout</a></li>
        </ul>
        <div class="hamburger" onclick="toggleMenu()">
            <div class="line"></div>
            <div class="line"></div>
            <div class="line"></div>
        </div>
    </nav>
</header>

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
                    <a href="marksAnalysis.php?module_id=' . $row['ModuleID'] . '">View Marks Analysis</a>
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
document.getElementById('navbar-toggler').addEventListener('click', function() {
    const menu = document.getElementById('navbar-menu');
    menu.classList.toggle('active');
});</script>


</body>
</html>
