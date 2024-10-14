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
            echo "<script>
                alert('Attendance recorded successfully!');
                window.location.href = 'studentDashboard.php';
            </script>";
        } else {
            echo "<script>
                alert('Error recording attendance: " . $stmt->error . "');
            </script>";
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
    <style>
        /* Reset some default styles */
body {
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
    background-color: #f4f4f4; /* Light grey background */
}

/* Center the form on the page */
form {
    max-width: 400px; /* Max width for the form */
    margin: 100px auto; /* Center form with auto margins */
    padding: 20px;
    background: white; /* White background for form */
    border-radius: 8px; /* Rounded corners */
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Subtle shadow */
}

/* Styling the form labels */
label {
    display: block; /* Labels on new lines */
    margin-bottom: 8px; /* Space below labels */
    font-weight: bold; /* Bold labels */
}

/* Styling input fields */
input[type="text"] {
    width: 100%; /* Full width */
    padding: 10px; /* Padding inside input */
    border: 1px solid #ccc; /* Light grey border */
    border-radius: 4px; /* Rounded corners */
    margin-bottom: 20px; /* Space below inputs */
}

/* Styling the submit button */
button {
    width: 100%; /* Full width button */
    padding: 10px; /* Padding inside button */
    background-color: #28a745; /* Green background */
    color: white; /* White text */
    border: none; /* No border */
    border-radius: 4px; /* Rounded corners */
    cursor: pointer; /* Pointer on hover */
    font-size: 16px; /* Larger font */
}

/* Button hover effect */
button:hover {
    background-color: #218838; /* Darker green on hover */
}

/* Error messages styling */
.error-message {
    color: red; /* Red color for errors */
    margin-top: 10px; /* Space above error messages */
}
</style>
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
