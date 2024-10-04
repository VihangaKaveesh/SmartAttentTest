<?php
session_start();
include '../db.php';

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
    header("Location: ../Student/qr-scanner.html");
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
    header("Location: ../Teacher/Teacher_Dashboard.html");
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
    header("Location: ../Management/management_dashboard.html");
    exit();
}

// If no user found
echo "Invalid username or password.";
?>
