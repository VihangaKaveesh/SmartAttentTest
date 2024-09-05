<?php

// Include the database connection file
include 'db.php';

// Check if the registration form has been submitted
if (isset($_POST['submit'])) {

    // Sanitize and store form input values
    $username = filter_var($_POST['username'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $password = $_POST['password'];  // Retrieve the raw password
    $name = filter_var($_POST['name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone_number = filter_var($_POST['phone_number'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $course = filter_var($_POST['course'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Validate name (letters only)
    if (!preg_match("/^[a-zA-Z]+$/", $name)) {
        $message[] = 'Name should contain only letters!';
    }
    // Validate email format using regex
    elseif (!preg_match("/^[^\s@]+@[^\s@]+\.[^\s@]+$/", $email)) {
        $message[] = 'Please enter a valid email address!';
    }
    // Validate phone number format using regex (Sri Lankan number format)
    elseif (!preg_match("/^(?:\+?94)?[0-9]{9,10}$/", $phone_number)) {
        $message[] = 'Please enter a valid phone number (Sri Lankan format)!';
    }
    // If all validations pass
    else {
        // Check if the username already exists
        $check_user = $conn->prepare("SELECT * FROM `students` WHERE username = ?");
        $check_user->execute([$username]);

        if ($check_user->rowCount() > 0) {
            // If the username already exists, show an error message
            $message[] = 'Username already exists, please choose another!';
        } else {
            // Encrypt the password using password_hash()
            $hashed_password = password_hash($password, PASSWORD_DEFAULT); // bcrypt by default

            // Insert the new student into the database
            $insert_student = $conn->prepare("INSERT INTO `students` (username, password, name, email, phone_number, course) VALUES (?, ?, ?, ?, ?, ?)");
            $insert_student->execute([$username, $hashed_password, $name, $email, $phone_number, $course]);

            // Redirect to the login page after successful registration
            if ($insert_student) {
                $message[] = 'Registration successful! You can now login.';
                header('location:login.html');
            } else {
                $message[] = 'Registration failed, please try again.';
            }
        }
    }
}

?>
