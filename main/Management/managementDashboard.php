<?php
session_start();

// Check if the user is logged in as management
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'management') {
    header("Location: ../login/login.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Dashboard</title>
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
    overflow-x: auto; /* Horizontal scroll */
    padding: 30px;
    scroll-snap-type: x mandatory;
    scrollbar-width: none; /* Hide scrollbar for Firefox */
    width: 100%; /* Full width */
    gap: 30px; /* Space between items */
}

.dashboard-scroll::-webkit-scrollbar {
    display: none; /* Hide scrollbar for Chrome, Safari, and Edge */
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
        <a href="manageStudents.php">Students</a>
        <a href="addModules.php">Modules</a>
        <a href="manageTeachers.php">Teachers</a>
        <a href="notice.php">Notices</a>
        <a href="../login/login.html">Logout</a>
    </div>
</div>

<div class="dashboard-container">
    <h1>Management Dashboard</h1>
    <div class="dashboard-scroll">
        <a href="manageStudents.php" class="dashboard-item" title="Manage students">
            <i class="fas fa-user-graduate"></i>
            <p>Students</p>
        </a>
        <a href="addModules.php" class="dashboard-item" title="Add modules">
            <i class="fas fa-book"></i>
            <p>Modules</p>
        </a>
        <a href="manageTeachers.php" class="dashboard-item" title="Manage teachers">
            <i class="fas fa-chalkboard-teacher"></i>
            <p>Teachers</p>
        </a>
        <a href="notice.php" class="dashboard-item" title="View notices">
            <i class="fas fa-bullhorn"></i>
            <p>Notices</p>
        </a>
        <a href="addLabs.php" class="dashboard-item">
            <i class="fas fa-flask"></i>
            <p>Labs</p>
        </a>
        <a href="../login/login.html" class="dashboard-item">
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
