<?php
// Include database connection
include 'db.php'; // This file contains the code to connect to your database

// Check if teacher ID is provided through the URL parameter
if (isset($_GET['id'])) {
   $teacher_id = $_GET['id']; // Get the teacher ID from the URL

   // Fetch current details of the teacher from the database using the teacher ID
   $select_teacher = $conn->prepare("SELECT * FROM `teachers` WHERE teacher_id = ?");
   $select_teacher->execute([$teacher_id]);
   $teacher = $select_teacher->fetch(PDO::FETCH_ASSOC); // Fetch the teacher's data as an associative array

   // If the teacher is not found, display an error message and exit the script
   if (!$teacher) {
      echo "Teacher not found!";
      exit();
   }

   // Check if the update form has been submitted
   if (isset($_POST['update'])) {
      // Sanitize and store the form input values
      $username = filter_var($_POST['username'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
      $name = filter_var($_POST['name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
      $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
      $phone_number = filter_var($_POST['phone_number'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
      $department = filter_var($_POST['department'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

      // Prepare the SQL query to update the teacher's details in the database
      $update_teacher = $conn->prepare("UPDATE `teachers` SET username = ?, name = ?, email = ?, phone_number = ?, department = ? WHERE teacher_id = ?");
      
      // Execute the query with the new values
      $update_teacher->execute([$username, $name, $email, $phone_number, $department, $teacher_id]);

      // After updating, redirect the user back to the teacher list page
      header('Location: view_teachers.php');
      exit();
   }
} else {
   // If no teacher ID is provided in the URL, display an error message and exit the script
   echo "No teacher ID provided!";
   exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Update Teacher</title> <!-- Page title -->
</head>
<body>

<h2>Update Teacher Details</h2> <!-- Heading for the form -->

<!-- Form for updating the teacher's details -->
<form action="" method="post">
   <!-- Display the current username in an input field, which can be modified -->
   <label>Username:</label>
   <input type="text" name="username" value="<?= $teacher['username']; ?>" required><br><br>

   <!-- Display the current name in an input field, which can be modified -->
   <label>Name:</label>
   <input type="text" name="name" value="<?= $teacher['name']; ?>" required><br><br>

   <!-- Display the current email in an input field, which can be modified -->
   <label>Email:</label>
   <input type="email" name="email" value="<?= $teacher['email']; ?>" required><br><br>

   <!-- Display the current phone number in an input field, which can be modified -->
   <label>Phone Number:</label>
   <input type="text" name="phone_number" value="<?= $teacher['phone_number']; ?>"><br><br>

   <!-- Dropdown for selecting the department, with the current department pre-selected -->
   <label>Department:</label>
   <select name="department" required>
      <option value="" disabled>Select Department</option>
      <option value="Computer Science" <?= $teacher['department'] == 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
      <option value="Mathematics" <?= $teacher['department'] == 'Mathematics' ? 'selected' : ''; ?>>Mathematics</option>
      <option value="Physics" <?= $teacher['department'] == 'Physics' ? 'selected' : ''; ?>>Physics</option>
      <option value="Chemistry" <?= $teacher['department'] == 'Chemistry' ? 'selected' : ''; ?>>Chemistry</option>
      <option value="Engineering" <?= $teacher['department'] == 'Engineering' ? 'selected' : ''; ?>>Engineering</option>
      <option value="Business Administration" <?= $teacher['department'] == 'Business Administration' ? 'selected' : ''; ?>>Business Administration</option>
      <option value="Economics" <?= $teacher['department'] == 'Economics' ? 'selected' : ''; ?>>Economics</option>
      <option value="Literature" <?= $teacher['department'] == 'Literature' ? 'selected' : ''; ?>>Literature</option>
   </select><br><br>

   <!-- Submit button for updating the teacher's details -->
   <input type="submit" name="update" value="Update Teacher">
</form>

</body>
</html>
