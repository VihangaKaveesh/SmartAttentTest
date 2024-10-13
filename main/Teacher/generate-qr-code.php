<?php
// generate-qr-code.php
session_start();

// Database connection
include '../db.php';

// Ensure the teacher is logged in
if ($_SESSION['role'] != 'teacher') {
    die("Access Denied");
}

// Get module and lab selections
$module_id = $_POST['module_id'];
$lab_id = $_POST['lab_id'];
$teacher_id = $_SESSION['teacher_id'];

// Fetch the lab's location
$query = "SELECT Latitude, Longitude FROM Labs WHERE LabID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $lab_id);
$stmt->execute();
$lab_result = $stmt->get_result()->fetch_assoc();

if (!$lab_result) {
    die("Lab not found.");
}

// Insert session details into the Sessions table
$query = "INSERT INTO Sessions (TeacherID, ModuleID, LabID) VALUES (?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $teacher_id, $module_id, $lab_id);

if ($stmt->execute()) {
    $session_id = $stmt->insert_id; // Get the newly created SessionID

    // Generate QR code data with SessionID
    $qr_data = [
        'module_id' => $module_id,
        'lab_id' => $lab_id,
        'session_id' => $session_id, // Include the SessionID
        'timestamp' => date('Y-m-d H:i:s')
    ];

    // Log QR data for debugging
    error_log("QR Data for Code: " . json_encode($qr_data));

    // Generate QR code using PHP QR Code library
    include '../phpqrcode/qrlib.php'; // Adjusted path as necessary

    // Set the path where the QR code will be saved
    $qrCodeFilePath = '../qrcodes/qr-code.png';

    // Generate the QR code image
    QRcode::png(json_encode($qr_data), $qrCodeFilePath, QR_ECLEVEL_L, 10);
} else {
    die("Error recording session: " . $stmt->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate QR Code</title>
    <style>
        /* General Styling */
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #4CAF50;
            color: #4CAF50;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            box-sizing: border-box;
        }

        /* Card Container for QR Code */
        .qr-container {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
            max-width: 400px;
            width: 100%;
            margin: 0 auto;
        }

        .qr-container img {
            width: 100%;
            max-width: 250px;
            height: auto;
            margin-bottom: 20px;
        }

        .qr-container h1 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #0a72b1;
        }

        .qr-container p {
            font-size: 1rem;
            color: #666;
            margin-bottom: 20px;
        }

        /* Button Styles */
        .qr-container button {
            background-color: #0a72b1;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .qr-container button:hover {
            background-color: #055a88;
        }

        /* Mobile Responsiveness */
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }

            .qr-container {
                padding: 15px;
            }

            .qr-container h1 {
                font-size: 1.2rem;
            }

            .qr-container p {
                font-size: 0.9rem;
            }

            .qr-container button {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
        }

        /* Sidebar Styling */
        .hamburger {
            font-size: 2rem;
            cursor: pointer;
            margin: 10px;
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 2000;
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
            <a href="../login/login.php">Logout</a>
        </div>
    </div>

    <!-- QR Code Display Section -->
    <div class="qr-container">
        <h1>Session QR Code</h1>
        <img src="<?php echo $qrCodeFilePath; ?>" alt="QR Code" />
        <p>Session recorded successfully!</p>
        <button onclick="window.location.href='teacherDashboard.php'">Back to Dashboard</button>
    </div>

    <!-- Script to toggle sidebar -->
    <script>
        const hamburger = document.querySelector('.hamburger');
        const sidebar = document.querySelector('.sidebar');

        hamburger.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    </script>

</body>
</html>
