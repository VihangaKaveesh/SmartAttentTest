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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <style>
     /* Global Styling */
body {
    margin: 0;
    padding: 0;
    background-color: #f0f4f8;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Container for the whole form and table */
.container {
    max-width: 900px;
    margin: 20px auto;
    padding: 30px;
    background-color: white;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    border-radius: 12px;
}

/* Card structure for form and content */
.card {
    padding: 25px;
    background-color: #ffffff;
    border: 1px solid #ddd;
    border-radius: 10px;
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

/* Header Titles */
h2, h3 {
    text-align: center;
    color: #4CAF50;
    font-size: 24px;
    margin-bottom: 25px;
}

/* Form Styling */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    font-size: 18px;
    font-weight: bold;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 12px;
    font-size: 16px;
    border: 1px solid #ccc;
    border-radius: 8px;
    box-sizing: border-box;
    margin-top: 10px;
}

button {
    background-color: #4CAF50;
    color: white;
    padding: 15px;
    width: 100%;
    border: none;
    border-radius: 8px;
    font-size: 18px;
    cursor: pointer;
    transition: background-color 0.3s;
}

button:hover {
    background-color: #388E3C;
}

/* Success and Error Messages */
.success-message, .error-message {
    text-align: center;
    font-size: 16px;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.success-message {
    color: green;
    background-color: #eaf5ea;
}

.error-message {
    color: red;
    background-color: #f5eaea;
}

/* Table Styling */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

table th, table td {
    padding: 16px;
    text-align: left;
    border: 1px solid #ddd;
    font-size: 16px;
}

table th {
    background-color: #4CAF50;
    color: white;
}

table td a {
    color: #4CAF50;
    text-decoration: none;
    font-weight: bold;
}

table td a:hover {
    text-decoration: underline;
    color: #388E3C;
}

/* Sidebar Menu */
.hamburger {
    font-size: 2rem;
    cursor: pointer;
    position: fixed;
    top: 20px;
    left: 20px;
}

.sidebar {
    position: fixed;
    top: 0;
    left: -100%;
    height: 100%;
    width: 100vw;
    background-color: #4CAF50;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    transition: left 0.3s ease;
    z-index: 1500;
}

.sidebar.active {
    left: 0;
}

.nav-links a {
    color: white;
    padding: 20px;
    margin: 15px 0;
    font-size: 20px;
    text-decoration: none;
    text-align: center;
    width: 100%;
    transition: all 0.3s;
}

.nav-links a:hover {
    background-color: #388E3C;
    border-radius: 8px;
    transform: scale(1.05);
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        padding: 20px;
    }
    table th, table td {
        font-size: 14px;
        padding: 12px;
    }
    button {
        padding: 12px;
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

<?php
$conn->close();
?>
