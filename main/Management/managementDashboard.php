<?php
session_start();

// Check if the user is logged in as management
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'management') {
    header("Location: ../login/login.php");
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
              body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f7fa;
            color: #333;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        h1 {
            font-size: 2.5rem;
            text-align: center;
            margin: 20px 0;
            color: #333;
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
            background-color:#a03aba;
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
            background-color: #d448f7;
            border-radius: 5px;
            transform: translateY(-5px);
        }

        /* Dashboard Container */
        .dashboard-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            max-width: 1200px;
            margin: auto;
        }

        /* Updated dashboard-scroll class */
        .dashboard-scroll {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 130px;
            justify-items: center;
            width: 100%;
            padding: 30px;
            margin-top: 20px;
        }

        /* Dashboard Item Cards */
        .dashboard-item {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-decoration: none;
            color: #333;
            transition: transform 0.3s, box-shadow 0.3s;
            width: 220px;
        }

        .dashboard-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .dashboard-item i {
            font-size: 3rem;
            color: #4CAF50;
        }

        .dashboard-item p {
            margin-top: 10px;
            font-size: 1.2rem;
            font-weight: bold;
        }

        /* Footer Styling */
        .footer {
            margin-top: 40px;
            background-color: #4CAF50;
            padding: 10px;
            color: white;
            text-align: center;
            font-size: 0.9rem;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .dashboard-item {
                padding: 15px;
            }

            .dashboard-item i {
                font-size: 2.5rem;
            }

            .dashboard-item p {
                font-size: 1rem;
            }

            .nav-links a {
                padding: 15px;
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
        <a href="../login/login.php">Logout</a>
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
        <a href="../login/login.php" class="dashboard-item">
            <i class="fas fa-sign-out-alt"></i>
            <p>Logout</p>
        </a>
    </div>
</div>

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
