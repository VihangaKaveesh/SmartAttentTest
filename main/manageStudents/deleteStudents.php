<?php

// Include database connection
include 'db.php';

// Check if the `id` is passed via the URL
if (isset($_GET['id'])) {
   $student_id = $_GET['id'];

   // Prepare the delete statement
   $delete_student = $conn->prepare("DELETE FROM `students` WHERE student_id = ?");
   $delete_student->execute([$student_id]);

   // Redirect back to the student list after deletion
   header('Location: view_students.php');
   exit();
} else {
   echo "No student ID provided!";
}

?>
