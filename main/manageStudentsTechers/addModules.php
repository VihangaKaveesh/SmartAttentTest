<?php
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

// Function to check if a class name already exists
function isClassNameExists($class_name, $class_id = null) {
    $conn = connectDB();
    $query = "SELECT module_id FROM modules WHERE module_name = ?";
    
    // If updating, exclude the current class by ID
    if ($class_id !== null) {
        $query .= " AND module_id != ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $class_name, $class_id);
    } else {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $class_name);
    }

    $stmt->execute();
    $stmt->store_result();

    $exists = $stmt->num_rows > 0;
    $stmt->close();
    $conn->close();
    return $exists;
}

// Initialize class variables
$class_name = "";
$class_id = null;

// Handle adding a class
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_class"])) {
    $class_name = filter_var($_POST["class_name"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if (!empty($class_name)) {
        if (isClassNameExists($class_name)) {
            echo "<p class='error'>Class name already exists. Please choose a different name.</p>";
        } else {
            $conn = connectDB();
            $stmt = $conn->prepare("INSERT INTO classes (class_name) VALUES (?)");
            $stmt->bind_param("s", $class_name);

            if ($stmt->execute()) {
                echo "<p class='success'>Class added successfully.</p>";
                $class_name = ""; // Clear the class name after addition
            } else {
                echo "<p class='error'>Error adding class: " . $stmt->error . "</p>";
            }

            $stmt->close();
            $conn->close();
        }
    } else {
        echo "<p class='error'>Class name cannot be empty.</p>";
    }
}

// Handle updating a class
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_class"])) {
    $class_id = $_POST["class_id"];
    $class_name = filter_var($_POST["class_name"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if (!empty($class_name)) {
        if (isClassNameExists($class_name, $class_id)) {
            echo "<p class='error'>Class name already exists. Please choose a different name.</p>";
        } else {
            $conn = connectDB();
            $stmt = $conn->prepare("UPDATE classes SET class_name = ? WHERE class_id = ?");
            $stmt->bind_param("si", $class_name, $class_id);

            if ($stmt->execute()) {
                $message[] = 'Student updated successfully!';
                // Redirect to reset the form to "Add Student" mode
                header("Location: addClasses.php"); 
                exit(); // Exit after redirection
            } else {
                $message[] = 'Error: Could not update student.';
            }
            $stmt->close();
        }
    } else {
        echo "<p class='error'>Class name cannot be empty.</p>";
    }
}

// Handle deleting a class
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $class_id = $_GET['id'];
    
    $conn = connectDB();
    $stmt = $conn->prepare("DELETE FROM classes WHERE class_id = ?");
    $stmt->bind_param("i", $class_id);
    
    if ($stmt->execute()) {
        echo "<p class='success'>Class deleted successfully.</p>";
    } else {
        echo "<p class='error'>Error deleting class: " . $stmt->error . "</p>";
    }

    $stmt->close();
    $conn->close();
}

// Fetch the class if in edit mode
$class_to_edit = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $class_id = $_GET['id'];
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT * FROM classes WHERE class_id = ?");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $class_to_edit = $result->fetch_assoc();
        // Set the class name to the value fetched from the database for editing
        $class_name = $class_to_edit['class_name'];
    }
    $stmt->close();
    $conn->close();
}
?>

<!-- Form to add or update a class -->
<form action="" method="post">
    <label for="class_name">Class Name:</label>
    <input type="text" id="class_name" name="class_name" required value="<?php echo htmlspecialchars($class_name); ?>">
    
    <?php if ($class_to_edit): ?>
        <input type="hidden" name="class_id" value="<?php echo htmlspecialchars($class_to_edit['class_id']); ?>">
        <input type="submit" name="update_class" value="Update Class">
    <?php else: ?>
        <input type="submit" name="add_class" value="Add Class">
    <?php endif; ?>
</form>

<!-- Display the list of classes with Edit and Delete options -->
<h2>Classes List</h2>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Class Name</th>
        <th>Actions</th>
    </tr>
    <?php
    // Fetch and display the list of classes
    $conn = connectDB();
    $result = $conn->query("SELECT class_id, class_name FROM classes");

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['class_id']) . '</td>';
            echo '<td>' . htmlspecialchars($row['class_name']) . '</td>';
            echo '<td>';
            echo '<a href="?action=edit&id=' . htmlspecialchars($row['class_id']) . '">Edit</a> | ';
            echo '<a href="?action=delete&id=' . htmlspecialchars($row['class_id']) . '" onclick="return confirm(\'Are you sure you want to delete this class?\');">Delete</a>';
            echo '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="3">No classes found.</td></tr>';
    }

    $conn->close();
    ?>
</table>
