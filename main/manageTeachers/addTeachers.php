<?php
// Include the database connection file
include 'db.php';

// Check if the registration form has been submitted
if (isset($_POST['submit'])) {

    // Sanitize and store form input values
    $username = filter_var($_POST['username'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Encrypt the password using bcrypt
    $name = filter_var($_POST['name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone_number = filter_var($_POST['phone_number'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $department = filter_var($_POST['department'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Validate name (letters and spaces only)
    if (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $message[] = 'Name should contain only letters and spaces!';
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
        // Check if the username already exists in the teachers table
        $check_user = $conn->prepare("SELECT * FROM `teachers` WHERE username = ?");
        $check_user->execute([$username]);

        if ($check_user->rowCount() > 0) {
            // If the username already exists, show an error message
            $message[] = 'Username already exists, please choose another!';
        } else {
            // Insert the new teacher into the teachers table in the database
            $insert_teacher = $conn->prepare("INSERT INTO `teachers` (username, password, name, department, email, phone_number) VALUES (?, ?, ?, ?, ?, ?)");
            $insert_teacher->execute([$username, $password, $name, $department, $email, $phone_number]);

            // If the insertion is successful, show a success message and redirect to the teachers' list page
            if ($insert_teacher) {
                $message[] = 'Registration successful!';
                header('location:view_teachers.php'); // Redirect to the list of teachers
            } else {
                // If the insertion fails, show an error message
                $message[] = 'Registration failed, please try again.';
            }
        }
    }
}
?>

<!-- Add Teacher Form (HTML) -->
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Register Teacher</title> <!-- Page title -->
</head>
<body>

<h2>Register a New Teacher</h2> <!-- Page heading -->

<!-- Registration form for adding a new teacher -->
<form action="" method="post">
   <label>Username:</label>
   <!-- Input field for username -->
   <input type="text" name="username" required><br><br>

   <label>Password:</label>
   <!-- Input field for password -->
   <input type="password" name="password" required><br><br>

   <label>Name:</label>
   <!-- Input field for teacher's name -->
   <input type="text" name="name" required><br><br>

   <label>Email:</label>
   <!-- Input field for teacher's email -->
   <input type="email" name="email" required><br><br>

   <label>Phone Number:</label>
   <!-- Input field for phone number (optional) -->
   <input type="text" name="phone_number"><br><br>

   <label>Department:</label>
   <!-- Dropdown menu to select the department -->
   <select name="department" required>
      <option value="" disabled>Select Department</option> <!-- Placeholder option -->
      <!-- List of department options -->
      <option value="Software Engineering">Software Engineering</option>
      <option value="Business">Business</option>
      <option value="Networking">Networking</option>
      <option value="Cyber Security">Cyber Security</option>
      <option value="Engineering">Engineering</option>
   </select><br><br>

   <!-- Submit button to register the teacher -->
   <input type="submit" name="submit" value="Register Teacher">
</form>

</body>
</html>
