<?php
session_start();
include '../db.php';

// Ensure the student is logged in
if ($_SESSION['role'] != 'student') {
    die("Access Denied");
}

// Get the submitted verification code and session ID
$submitted_code = $_POST['verification_code'];
$session_id = $_POST['session_id'];

// Check if the verification code matches
if ($submitted_code == $_SESSION['verification_code']) {
    // Insert attendance record
    $student_id = $_SESSION['student_id'];
    $insert_query = "INSERT INTO Attendance (StudentID, SessionID, Status) VALUES (?, ?, 'present')";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ii", $student_id, $session_id);

    if ($stmt->execute()) {
        echo "Attendance recorded successfully!";
    } else {
        echo "Error recording attendance: " . $stmt->error;
    }
} else {
    echo "Invalid verification code.";
}
?>
