<?php
session_start();

// Check if the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['verification_code']) && isset($_SESSION['verification_code'])) {
        $entered_code = trim($_POST['verification_code']);

        // Validate the entered code
        if ($entered_code === $_SESSION['verification_code']) {
            include '../db.php';  // Include your database connection file
            $student_id = $_SESSION['student_id'];
            $class_id = $_SESSION['class_id'];
            $attendance_time = date('Y-m-d H:i:s');

            // Insert attendance record into the database
            $query = "INSERT INTO attendance (student_id, class_id, attendance_time) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iis", $student_id, $class_id, $attendance_time);

            if ($stmt->execute()) {
                // Unset the session variables after successful attendance recording
                unset($_SESSION['verification_code']);
                unset($_SESSION['class_id']);
                $message = "<p style='color:green;'>Attendance recorded successfully.</p>";
            } else {
                $message = "<p style='color:red;'>Error recording attendance. Please try again.</p>";
            }
        } else {
            $message = "<p style='color:red;'>Invalid verification code. Please try again.</p>";
        }
    } else {
        $message = "<p style='color:red;'>Verification code is missing or session has expired.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        h1 {
            color: #333;
        }
        form {
            margin: 20px 0;
        }
        input[type="text"] {
            padding: 10px;
            font-size: 16px;
            margin-bottom: 10px;
        }
        button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .message {
            font-size: 18px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <h1>Verify Your Attendance</h1>
    <p>Please enter the verification code sent to your email to complete the attendance process.</p>

    <form method="POST">
        <label for="verification_code">Verification Code:</label><br>
        <input type="text" name="verification_code" id="verification_code" required><br>
        <button type="submit">Submit</button>
    </form>

    <div class="message">
        <?php
        // Display feedback message after form submission
        if (isset($message)) {
            echo $message;
        }
        ?>
    </div>
</body>
</html>
