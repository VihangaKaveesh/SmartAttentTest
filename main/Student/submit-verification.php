<?php
session_start();
include '../db.php';

// Ensure the student is logged in
if ($_SESSION['role'] != 'student') {
    die("Access Denied");
}

// Check if email was sent
if (!isset($_SESSION['email_sent']) || !$_SESSION['email_sent']) {
    die("Verification email not sent. Please try scanning the QR code again.");
}

// Check if the session ID is stored in session
if (!isset($_SESSION['session_id'])) {
    die("Session ID is missing. Please try scanning the QR code again.");
}

$session_id = $_SESSION['session_id']; // Retrieve the session ID

// Process the verification form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the submitted verification code
    $submitted_code = $_POST['verification_code'];

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

        // Clear session variables after successful attendance
        unset($_SESSION['verification_code']);
        unset($_SESSION['session_id']);
        unset($_SESSION['email_sent']);
    } else {
        echo "Invalid verification code.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Attendance</title>
</head>
<body>
    <form method="POST">
        <label for="verification_code">Enter Verification Code:</label>
        <input type="text" name="verification_code" required>
        <input type="hidden" name="session_id" value="<?php echo htmlspecialchars($session_id); ?>">
        <button type="submit">Verify</button>
    </form>
</body>
</html>
