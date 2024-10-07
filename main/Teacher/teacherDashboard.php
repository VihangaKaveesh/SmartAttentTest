<?php
session_start();

// Check if the user is logged in as a teacher
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login/login.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html, body {
    height: 100%;
    font-family: 'Orbitron', sans-serif;
    background: linear-gradient(135deg, #74ebd5 0%, #ACB6E5 100%); /* Gradient background */
    color: #000000; /* Black text color */
    overflow-x: hidden; /* Prevent horizontal scrolling */
}

/* Loading Screen */
#loading {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: rgba(255, 255, 255, 0.8); /* Semi-transparent background */
    z-index: 9999; /* Above all other content */
    transition: opacity 0.3s ease;
}

.loading-text {
    font-size: 2rem;
    color: #74ebd5; /* Loading text color */
}

.nav-bar {
    display: flex;
    justify-content: center; /* Center items horizontally */
    align-items: center;
    padding: 20px 0; /* Vertical padding only */
    background-color: #f2f2f2; /* Light background for navbar */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1000;
}

.nav-links {
    display: flex; /* Use flexbox to arrange nav items */
}

.nav-links a {
    color: #000000; /* Black link color */
    text-decoration: none;
    margin: 0 15px;
    font-size: 1.3rem;
    transition: color 0.3s;
}

.nav-links a:hover {
    color: #74ebd5; /* Aqua hover color */
    text-decoration: underline;
}

.dashboard-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 100px; /* Space for navbar */
    width: 100%;
}

h1 {
    font-size: 4rem;
    margin-bottom: 40px;
    color: #333; /* Darker text color for heading */
    text-align: center; /* Center the heading */
}

.dashboard-scroll {
    display: flex;
    overflow-x: auto; /* Enable horizontal scrolling */
    padding: 30px;
    scroll-snap-type: x mandatory; /* Snap scrolling for the cards */
    scrollbar-width: thin; /* Hide scrollbar for Firefox */
    width: 100%; /* Full width */
    gap: 30px; /* Space between items */
}

.dashboard-scroll::-webkit-scrollbar {
    height: 8px;
}
.dashboard-scroll::-webkit-scrollbar-thumb {
    background-color: #0073e6; /* Blue scrollbar thumb color */
    border-radius: 10px; /* Round scrollbar thumb */
}


.dashboard-item {
    background-color: #fff; /* White background for items */
    width: 500px; /* Card width */
    height: 600px; /* Card height */
    text-align: center;
    padding: 20px; /* Adjusted padding */
    flex-shrink: 0;
    scroll-snap-align: start;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2); /* Lighter shadow for visibility */
    transition: transform 0.3s, box-shadow 0.3s;
    display: flex; /* Use flexbox to center align content */
    flex-direction: column; /* Align items in a column */
    justify-content: center; /* Center vertically */
    align-items: center; /* Center horizontally */
    border-radius: 15px; /* Rounded corners for items */
    position: relative; /* Position for reflection effect */
}

.dashboard-item:hover {
    transform: scale(1.05); /* Scale effect on hover */
    box-shadow: 0 8px 40px rgba(0, 0, 0, 0.4); /* Enhanced shadow on hover */
}

.dashboard-item i {
    font-size: 4rem; /* Adjusted icon size */
    margin-bottom: 20px;
    color: #74ebd5; /* Aqua icon color */
}

.dashboard-item p {
    font-size: 1.7rem; /* Adjusted text size */
    color: #333; /* Darker text color */
}

/* Mobile Responsive Queries */
@media (max-width: 768px) {
    h1 {
        font-size: 2.5rem; /* Adjust heading size */
    }
    .dashboard-item {
        width: 300px; /* Width for tablets */
        height: 400px; /* Adjust height */
    }
}

@media (max-width: 480px) {
    .nav-links {
        flex-direction: column;
        padding: 10px;
    }
    .nav-links a {
        margin: 10px 0;
        font-size: 1.1rem; /* Adjust link size */
    }
    h1 {
        font-size: 2rem; /* Adjust heading size */
    }
    .dashboard-item {
        width: 90%; /* Full width on mobile */
        height: 300px; /* Adjust height */
    }
    .dashboard-item i {
        font-size: 2.5rem; /* Adjust icon size */
    }
    .dashboard-item p {
        font-size: 1.2rem; /* Adjust text size */
    }
}

    </style>
</head>
<body>

<div id="loading">
    <div class="loading-text">Loading...</div>
</div>

<div class="nav-bar">
    <div class="nav-links">
        <a href="teacher_profile.php">Profile</a>
        <a href="Teacher-qr-generator.php">QR Code</a>
        <a href="Assignments-upload.php">Upload Assignments</a>
        <a href="sessionAnalysis.php">Session Analysis</a>
        <a href="lecture_material_upload.php">Lecture Materials</a>
        <a href="../login/login.html">Logout</a>
    </div>
</div>

<div class="dashboard-container">
    <h1>Teacher Dashboard</h1>
    <div class="dashboard-scroll">
        <a href="teacher_profile.php" class="dashboard-item" title="View your profile">
            <i class="fas fa-user"></i>
            <p>Profile</p>
        </a>
        <a href="Teacher-qr-generator.php" class="dashboard-item" title="Generate QR Code">
            <i class="fas fa-qrcode"></i>
            <p>QR Code</p>
        </a>
        <a href="Assignments-upload.php" class="dashboard-item" title="Upload Assignments">
            <i class="fas fa-file-upload"></i>
            <p>Upload Assignments</p>
        </a>
        <a href="sessionAnalysis.php" class="dashboard-item" title="Session Analysis">
            <i class="fas fa-chart-line"></i>
            <p>Session Analysis</p>
        </a>
        <a href="lecture_material_upload.php" class="dashboard-item" title="Upload Lecture Materials">
            <i class="fas fa-upload"></i>
            <p>Lecture Materials</p>
        </a>
        <a href="../login/login.html" class="dashboard-item" title="Log out of your account">
            <i class="fas fa-sign-out-alt"></i>
            <p>Logout</p>
        </a>
    </div>
</div>

<script>
    // Hide the loading screen once the content is loaded
    window.addEventListener('load', function() {
        const loadingScreen = document.getElementById('loading');
        loadingScreen.style.opacity = '0';
        setTimeout(() => {
            loadingScreen.style.display = 'none'; // Remove from view after fade out
        }, 300); // Matches CSS transition duration
    });
</script>

</body>
</html>
