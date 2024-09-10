<?php
session_start();
include '../db.php';

// Collect user credentials
$username = $_POST['username'];
$password = $_POST['password'];

// Check student credentials
$query = "SELECT student_id, name FROM students WHERE username = ? AND password = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $_SESSION['role'] = 'student';
    $_SESSION['student_id'] = $row['student_id'];
    $_SESSION['name'] = $row['name'];
    header("Location: ../Student/qr-scanner.html");
    exit();
}

// Check teacher credentials
$query = "SELECT teacher_id, name FROM teachers WHERE username = ? AND password = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $_SESSION['role'] = 'teacher';
    $_SESSION['teacher_id'] = $row['teacher_id'];
    $_SESSION['name'] = $row['name'];
    header("Location: ../Teacher/Teacher-qr-generator.php");
    exit();
}

// Check management credentials
$query = "SELECT management_id, name FROM management WHERE username = ? AND password = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $_SESSION['role'] = 'management';
    $_SESSION['management_id'] = $row['management_id'];
    $_SESSION['name'] = $row['name'];
    header("Location: management_dashboard.php");
    exit();
}

echo "Invalid username or password.";

?>
