<?php
session_start();
include '../db.php';

// Initialize error message
$error_message = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Collect user credentials
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check student credentials
    $query = "SELECT StudentID, FirstName, LastName FROM students WHERE Username = ? AND Password = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['role'] = 'student';
        $_SESSION['student_id'] = $row['StudentID'];
        $_SESSION['name'] = $row['FirstName'] . ' ' . $row['LastName'];
        header("Location: ../Student/studentDashboard.php");
        exit();
    }

    // Check teacher credentials
    $query = "SELECT TeacherID, FirstName, LastName FROM teachers WHERE Username = ? AND Password = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['role'] = 'teacher';
        $_SESSION['teacher_id'] = $row['TeacherID'];
        $_SESSION['name'] = $row['FirstName'] . ' ' . $row['LastName'];
        header("Location: ../Teacher/teacherDashboard.php");
        exit();
    }

    // Check management credentials
    $query = "SELECT ManagementID, FirstName, LastName FROM management WHERE Username = ? AND Password = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['role'] = 'management';
        $_SESSION['management_id'] = $row['ManagementID'];
        $_SESSION['name'] = $row['FirstName'] . ' ' . $row['LastName'];
        header("Location: ../Management/managementDashboard.php");
        exit();
    } else {
        // If no user found
        $error_message = "Invalid username or password.";
    
        // Show a JavaScript alert prompt
        echo "<script>alert('Invalid username or password. Please try again.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Login</title>
   <style>
/* General body styling */
body {
    margin: 0;
    padding: 0;
    font-family: 'Arial', sans-serif;
    background: linear-gradient(135deg, #74ebd5 0%, #ACB6E5 100%);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Styling the section container */
.form-container {
    background: #fff;
    padding: 5%;
    border-radius: 15px;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    width: 30%;
    max-width: 400px;
    min-width: 280px;
    text-align: center;
}

/* Styling the form headings */
h3 {
    margin-bottom: 5%;
    font-size: 2.2vw;
    color: #333;
}

/* Styling the input fields */
input[type="text"],
input[type="password"] {
    width: 90%;
    padding: 4%;
    margin: 5% 0;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1.2vw;
    transition: border-color 0.3s ease;
}

/* Adding focus effects on input fields */
input[type="text"]:focus,
input[type="password"]:focus {
    border-color: #74ebd5;
    outline: none;
}

/* Styling the submit button */
input[type="submit"] {
    width: 90%;
    padding: 4%;
    background: linear-gradient(135deg, #74ebd5 0%, #ACB6E5 100%);
    border: none;
    border-radius: 8px;
    font-size: 1.5vw;
    color: white;
    cursor: pointer;
    transition: background 0.3s ease;
}

/* Submit button hover effect */
input[type="submit"]:hover {
    background: linear-gradient(135deg, #ACB6E5 0%, #74ebd5 100%);
}

/* Responsive styling for large devices (tablets) */
@media (max-width: 1024px) {
    .form-container {
        width: 40%;
    }
    h3 {
        font-size: 2.5vw;
    }
    input[type="text"], input[type="password"] {
        font-size: 1.5vw;
    }
    input[type="submit"] {
        font-size: 1.8vw;
    }
}

/* Responsive styling for medium devices (phones) */
@media (max-width: 768px) {
    .form-container {
        width: 50%;
    }
    h3 {
        font-size: 4vw;
    }
    input[type="text"], input[type="password"] {
        font-size: 2vw;
    }
    input[type="submit"] {
        font-size: 2.5vw;
    }
}

/* Responsive styling for small devices (small phones) */
@media (max-width: 480px) {
    .form-container {
        width: 70%;
    }
    h3 {
        font-size: 5vw;
    }
    input[type="text"], input[type="password"] {
        font-size: 3.5vw;
    }
    input[type="submit"] {
        font-size: 3.8vw;
    }
}

.error-message {
    color: red;
    margin-bottom: 10px;
}

   </style>
</head>
<body>

<!-- Section for the login form -->
<section class="form-container">

   <!-- Form for user login -->
   <form action="login.php" method="post">
      <h3>Login Now</h3>
      
      <!-- Input for user ID (student, lecturer, management) with validation to remove spaces -->
      <input type="text" name="username" required placeholder="Enter your username" class="box" maxlength="50" oninput="this.value = this.value.replace(/\s/g, '')">
      
      <br>

      <!-- Input for password with validation to remove spaces -->
      <input type="password" name="password" required placeholder="Enter your password" class="box" maxlength="50" oninput="this.value = this.value.replace(/\s/g, '')">
      
      <br>

      <!-- Submit button for the login form -->
      <input type="submit" value="Login Now" name="submit" class="btn">
   </form>

</section>

</body>
</html>
