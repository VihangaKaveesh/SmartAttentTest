<?php
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
    $query = "SELECT module_id FROM modules WHERE module_name = ?";
    
    // If updating, exclude the current module by ID
    if ($module_id !== null) {
        $query .= " AND module_id != ?";
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

// Fetch teachers from the database for the dropdown
function getTeachers() {
    $conn = connectDB();
    $query = "SELECT teacher_id, name FROM teachers";
    $result = $conn->query($query);
    $teachers = [];

    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
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
    $module_name = filter_var($_POST["module_name"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $teacher_id = $_POST["teacher_id"]; // Get the selected teacher ID

    if (!empty($module_name) && !empty($teacher_id)) {
        if (isModuleNameExists($module_name)) {
            echo "<p class='error'>Module name already exists. Please choose a different name.</p>";
        } else {
            $conn = connectDB();
            $stmt = $conn->prepare("INSERT INTO modules (module_name, teacher_id) VALUES (?, ?)");
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
    $module_id = $_POST["module_id"];
    $module_name = filter_var($_POST["module_name"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $teacher_id = $_POST["teacher_id"];

    if (!empty($module_name) && !empty($teacher_id)) {
        if (isModuleNameExists($module_name, $module_id)) {
            echo "<p class='error'>Module name already exists. Please choose a different name.</p>";
        } else {
            $conn = connectDB();
            $stmt = $conn->prepare("UPDATE modules SET module_name = ?, teacher_id = ? WHERE module_id = ?");
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
    $stmt = $conn->prepare("DELETE FROM modules WHERE module_id = ?");
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
    $stmt = $conn->prepare("SELECT * FROM modules WHERE module_id = ?");
    $stmt->bind_param("i", $module_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $module_to_edit = $result->fetch_assoc();
        // Set the module name to the value fetched from the database for editing
        $module_name = $module_to_edit['module_name'];
        $teacher_id = $module_to_edit['teacher_id'];
    }
    $stmt->close();
    $conn->close();
}
?>

<!-- Form to add or update a module -->
<form action="" method="post">
    <label for="module_name">Module Name:</label>
    <input type="text" id="module_name" name="module_name" required value="<?php echo htmlspecialchars($module_name); ?>">

    <label for="teacher_id">Assign Teacher:</label>
    <select id="teacher_id" name="teacher_id" required>
        <option value="">Select a teacher</option>
        <?php
        // Populate the dropdown with teacher names
        $teachers = getTeachers();
        foreach ($teachers as $teacher) {
            $selected = ($teacher_id == $teacher['teacher_id']) ? 'selected' : '';
            echo "<option value='{$teacher['teacher_id']}' $selected>{$teacher['name']}</option>";
        }
        ?>
    </select>

    <?php if ($module_to_edit): ?>
        <input type="hidden" name="module_id" value="<?php echo htmlspecialchars($module_to_edit['module_id']); ?>">
        <input type="submit" name="update_module" value="Update Module">
    <?php else: ?>
        <input type="submit" name="add_module" value="Add Module">
    <?php endif; ?>
</form>

<!-- Display the list of modules with Edit and Delete options -->
<h2>Modules List</h2>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Module Name</th>
        <th>Assigned Teacher</th>
        <th>Actions</th>
    </tr>
    <?php
    // Fetch and display the list of modules
    $conn = connectDB();
    $result = $conn->query("SELECT m.module_id, m.module_name, t.name 
                            FROM modules m 
                            JOIN teachers t ON m.teacher_id = t.teacher_id");

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['module_id']) . '</td>';
            echo '<td>' . htmlspecialchars($row['module_name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['name']) . '</td>';
            echo '<td>';
            echo '<a href="?action=edit&id=' . htmlspecialchars($row['module_id']) . '">Edit</a> | ';
            echo '<a href="?action=delete&id=' . htmlspecialchars($row['module_id']) . '" onclick="return confirm(\'Are you sure you want to delete this module?\');">Delete</a>';
            echo '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="4">No modules found.</td></tr>';
    }

    $conn->close();
    ?>
</table>
