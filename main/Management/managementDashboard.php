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
    <!-- Include FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: #fff;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
        }

        .dashboard-container {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            max-width: 90%;
            width: 800px;
            padding: 40px;
            text-align: center;
            backdrop-filter: blur(10px);
        }

        h1 {
            font-size: 28px;
            margin-bottom: 30px;
            color: #ffffff;
            font-weight: 600;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 25px;
        }

        .dashboard-item {
            background: linear-gradient(135deg, #53D2DC 0%, #4284DB 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            transition: transform 0.4s ease-in-out, box-shadow 0.4s;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .dashboard-item i {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .dashboard-item p {
            margin: 0;
            font-size: 18px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        /* Hover Effects */
        .dashboard-item:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }

        .dashboard-item::before {
            content: "";
            position: absolute;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.2);
            top: -100%;
            left: -100%;
            transform: rotate(45deg);
            transition: 0.5s;
        }

        .dashboard-item:hover::before {
            top: 0;
            left: 0;
        }

        @media (max-width: 600px) {
            h1 {
                font-size: 22px;
            }

            .dashboard-item i {
                font-size: 32px;
            }

            .dashboard-item p {
                font-size: 16px;
            }

            .dashboard-grid {
                gap: 15px;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <h1>Management Dashboard</h1>
    <div class="dashboard-grid">
        <a href="manageStudents.php" class="dashboard-item">
            <i class="fas fa-user-graduate"></i>
            <p>Students</p>
        </a>
        <a href="addModules.php" class="dashboard-item">
            <i class="fas fa-book"></i>
            <p>Modules</p>
        </a>
        <a href="manageTeachers.php" class="dashboard-item">
            <i class="fas fa-chalkboard-teacher"></i>
            <p>Teachers</p>
        </a>
    </div>
</div>

</body>
</html>
