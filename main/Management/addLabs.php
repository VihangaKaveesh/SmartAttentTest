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

// Establish connection
$conn = connectDB();

// Initialize success and error messages
$successMsg = "";
$errorMsg = "";

// Add or update lab
if (isset($_POST['save'])) {
    $labName = trim($_POST['LabName']);
    $latitude = trim($_POST['Latitude']);
    $longitude = trim($_POST['Longitude']);
    $labID = $_POST['LabID'];

    // Validation: Check if all fields are filled
    if (empty($labName) || empty($latitude) || empty($longitude)) {
        $errorMsg = "All fields are required!";
    } else {
        // Check if the lab name already exists or if the same latitude and longitude combination exists (for adding new labs)
        if (empty($labID)) {
            $checkDuplicateName = "SELECT * FROM labs WHERE LabName = '$labName'";
            $duplicateNameResult = $conn->query($checkDuplicateName);
            
            $checkDuplicateLocation = "SELECT * FROM labs WHERE Latitude = '$latitude' AND Longitude = '$longitude'";
            $duplicateLocationResult = $conn->query($checkDuplicateLocation);

            if ($duplicateNameResult->num_rows > 0) {
                $errorMsg = "Error: A lab with this name already exists!";
            } elseif ($duplicateLocationResult->num_rows > 0) {
                $errorMsg = "Error: A lab with this latitude and longitude already exists!";
            } else {
                // Add new lab
                $sql = "INSERT INTO labs (LabName, Latitude, Longitude) VALUES ('$labName', '$latitude', '$longitude')";
                if ($conn->query($sql) === TRUE) {
                    $successMsg = "New lab added successfully!";
                } else {
                    $errorMsg = "Error: " . $sql . "<br>" . $conn->error;
                }
            }
        } else {
            // Update existing lab
            $sql = "UPDATE labs SET LabName = '$labName', Latitude = '$latitude', Longitude = '$longitude' WHERE LabID = '$labID'";
            if ($conn->query($sql) === TRUE) {
                $successMsg = "Lab updated successfully!";
            } else {
                $errorMsg = "Error updating lab: " . $conn->error;
            }
        }
    }
}

// Delete lab
if (isset($_GET['delete'])) {
    $labID = $_GET['delete'];
    $sql = "DELETE FROM labs WHERE LabID = $labID";
    if ($conn->query($sql) === TRUE) {
        $successMsg = "Lab deleted successfully!";
    } else {
        $errorMsg = "Error deleting lab: " . $conn->error;
    }
}

// Get lab data for editing
if (isset($_GET['edit'])) {
    $labID = $_GET['edit'];
    $result = $conn->query("SELECT * FROM labs WHERE LabID = $labID");
    $lab = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Labs</title>
</head>
<body>
<h2>Lab Management</h2>

<!-- Success or Error Messages -->
<?php if (!empty($successMsg)) { ?>
    <div style="color: green;"><?php echo $successMsg; ?></div>
<?php } elseif (!empty($errorMsg)) { ?>
    <div style="color: red;"><?php echo $errorMsg; ?></div>
<?php } ?>

<!-- Add/Edit Form -->
<form method="post" action="">
    <input type="hidden" name="LabID" value="<?php echo isset($lab['LabID']) ? $lab['LabID'] : ''; ?>">
    <label for="LabName">Lab Name:</label>
    <input type="text" name="LabName" value="<?php echo isset($lab['LabName']) ? $lab['LabName'] : ''; ?>" required>
    <br><br>
    <label for="Latitude">Latitude:</label>
    <input type="text" name="Latitude" value="<?php echo isset($lab['Latitude']) ? $lab['Latitude'] : ''; ?>" required>
    <br><br>
    <label for="Longitude">Longitude:</label>
    <input type="text" name="Longitude" value="<?php echo isset($lab['Longitude']) ? $lab['Longitude'] : ''; ?>" required>
    <br><br>
    <input type="submit" name="save" value="Save">
</form>

<br>

<!-- View Labs -->
<h3>Existing Labs</h3>
<table border="1" cellpadding="10">
    <tr>
        <th>Lab ID</th>
        <th>Lab Name</th>
        <th>Latitude</th>
        <th>Longitude</th>
        <th>Actions</th>
    </tr>
    <?php
    $result = $conn->query("SELECT * FROM labs");
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
            <td>" . $row['LabID'] . "</td>
            <td>" . $row['LabName'] . "</td>
            <td>" . $row['Latitude'] . "</td>
            <td>" . $row['Longitude'] . "</td>
            <td>
                <a href='?edit=" . $row['LabID'] . "'>Edit</a> |
                <a href='?delete=" . $row['LabID'] . "' onclick='return confirm(\"Are you sure?\")'>Delete</a>
            </td>
        </tr>";
    }
    ?>
</table>

</body>
</html>

<?php
$conn->close();
?>
