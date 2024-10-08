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
/* General Styling */
body {
    font-family: 'Poppins', sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 0;
}

h2, h3 {
    color: #5a4dcf;
    text-align: center; /* Optional: Center align headings */
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

.nav-links a:hover {
    background-color: #d448f7;
    border-radius: 5px;
    transform: translateY(-5px);
}

.nav-links a:hover::before {
    transform: scaleX(1);
    transform-origin: left;
}

/* Form Styling */
form {
    background-color: #fff;
    padding: 20px;
    border-radius: 10px; /* Updated to match theme */
    max-width: 500px;
    margin: 20px auto;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}

input[type="text"], select {
    width: 100%;
    padding: 10px;
    margin-bottom: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

input[type="submit"] {
    background-color: #5a4dcf; /* Updated color to match theme */
    color: white;
    cursor: pointer;
    border: none;
    border-radius: 5px;
    padding: 10px; /* Added padding for better UX */
    font-size: 1rem;
    transition: background 0.3s ease;
}

input[type="submit"]:hover {
    background-color: #4a3db0;
}

/* Table Styling */
table {
    width: 80%; /* Updated to match theme */
    border-collapse: collapse;
    margin: 20px auto;
    background-color: #fff; /* Ensured consistent styling */
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

th, td {
    padding: 12px;
    text-align: center;
    border: 1px solid #ddd;
}

th {
    background-color: #5a4dcf; /* Updated color to match theme */
    color: white;
}

td {
    color: #333;
}

/* Responsive Design */
@media (max-width: 768px) {
    form {
        padding: 10px;
    }

    table th, table td {
        font-size: 12px;
        padding: 8px;
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
