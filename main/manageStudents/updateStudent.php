<?php

// Include database connection
include 'db.php';

// Check if the `id` is passed via the URL
if (isset($_GET['id'])) {
   $student_id = $_GET['id'];

   // Fetch the student's current details from the database
   $select_student = $conn->prepare("SELECT * FROM `students` WHERE student_id = ?");
   $select_student->execute([$student_id]);
   $student = $select_student->fetch(PDO::FETCH_ASSOC);

   if (!$student) {
      echo "Student not found!";
      exit();
   }

   // Check if the update form has been submitted
   if (isset($_POST['update'])) {
      $username = filter_var($_POST['username'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
      $name = filter_var($_POST['name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
      $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
      $phone_number = filter_var($_POST['phone_number'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
      $course = filter_var($_POST['course'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

      // Update the student details in the database
      $update_student = $conn->prepare("UPDATE `students` SET username = ?, name = ?, email = ?, phone_number = ?, course = ? WHERE student_id = ?");
      $update_student->execute([$username, $name, $email, $phone_number, $course, $student_id]);

      // Redirect back to the student list after updating
      header('Location: view_students.php');
      exit();
   }

} else {
   echo "No student ID provided!";
   exit();
}

?>
