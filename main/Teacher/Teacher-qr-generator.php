<?php
// teacher-qr-generator.php
session_start();

// Database connection
include '../db.php';

// Check if the user is a teacher
if ($_SESSION['role'] != 'teacher') {
    die("Access Denied");
}

// Fetch the modules that the teacher is assigned to
$teacher_id = $_SESSION['teacher_id'];
$query = "SELECT ModuleID, ModuleName FROM Modules WHERE TeacherID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$modules_result = $stmt->get_result();

// Fetch the labs available
$query = "SELECT LabID, LabName FROM Labs";
$stmt = $conn->prepare($query);
$stmt->execute();
$labs_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts for the Orbitron font -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <title>Generate QR Code</title>
    <style>
        /* General styles for the form */
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    background-color: #f9f9f9;
}

form {
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    max-width: 400px;
    width: 90%;
}

label {
    font-weight: bold;
    margin-bottom: 10px;
    display: block;
}

select {
    width: 100%;
    padding: 10px;
    margin-bottom: 20px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 16px;
}

button {
    width: 100%;
    padding: 10px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 18px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

button:hover {
    background-color: #45a049;
}

/* Mobile styles */
@media (max-width: 600px) {
    form {
        padding: 15px;
    }

    label {
        font-size: 14px;
    }

    select, button {
        font-size: 14px;
        padding: 8px;
    }
}

/* Styles for larger screens (tablets and desktops) */
@media (min-width: 600px) and (max-width: 900px) {
    form {
        max-width: 600px;
    }
}

@media (min-width: 900px) {
    form {
        max-width: 500px;
    }

    button {
        font-size: 16px;
    }
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
            background-color: #4CAF50;
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
            background-color: #388E3C;
            border-radius: 5px;
            transform: translateY(-5px);
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
        <a href="teacher_profile.php">Profile</a><br><br><br><br><br>
        <a href="Teacher-qr-generator.php">QR Code</a><br><br><br><br><br>
        <a href="Assignments-upload.php">Upload Assignments</a><br><br><br><br><br>
        <a href="sessionAnalysis.php">Session Analysis</a><br><br><br><br><br>
        <a href="lecture_material_upload.php">Lecture Materials</a><br><br><br><br><br>
        <a href="../login/login.html">Logout</a>
    </div>
</div>

    <form action="generate-qr-code.php" method="POST">
        <label for="module">Select Module:</label>
        <select name="module_id" required>
            <?php
            while ($row = $modules_result->fetch_assoc()) {
                echo "<option value='{$row['ModuleID']}'>{$row['ModuleName']}</option>";
            }
            ?>
        </select>

        <label for="lab">Select Lab:</label>
        <select name="lab_id" required>
            <?php
            while ($row = $labs_result->fetch_assoc()) {
                echo "<option value='{$row['LabID']}'>{$row['LabName']}</option>";
            }
            ?>
        </select>

        <button type="submit">Generate QR Code</button>
    </form>
    <script>
    document.querySelector('.hamburger').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('active');
    });
</script>
</body>

</html>
