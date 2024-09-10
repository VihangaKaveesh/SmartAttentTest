<?php
session_start();

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $entered_code = $_POST['verification_code'];
    
    // Check if the entered code matches the session verification code
    if ($entered_code == $_SESSION['verification_code']) {
        // Verification successful, proceed to record attendance
        echo "Verification successful! Attendance marked.";
        
        // Record attendance in the database
        include '../db.php';
        $student_id = $_SESSION['student_id'];
        $class_id = $_SESSION['class_id']; // Get the stored class_id from session
        $attendance_time = date('Y-m-d H:i:s');
        
        // Insert attendance record into the database
        $query = "INSERT INTO attendance (student_id, class_id, attendance_time) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iis", $student_id, $class_id, $attendance_time);
        
        if ($stmt->execute()) {
            // Attendance recorded successfully, clear session variables
            unset($_SESSION['verification_code']);
            unset($_SESSION['class_id']);
            
            echo "Attendance recorded successfully.";
            exit();
        } else {
            echo "Error recording attendance.";
        }
    } else {
        echo "Invalid verification code. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify Email</title>
</head>
<body>
    <h1>Verify Your Email</h1>
    <form method="POST">
        <label for="verification_code">Enter the verification code sent to your email:</label>
        <input type="text" name="verification_code" required>
        <button type="submit">Verify</button>
    </form>
</body>
</html>
