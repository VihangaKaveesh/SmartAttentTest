<?php
// Include database connection
include 'db.php';

// Check if teacher ID is provided
if (isset($_GET['id'])) {
   $teacher_id = $_GET['id'];

   // Delete the teacher from the database
   $delete_teacher = $conn->prepare("DELETE FROM `teachers` WHERE teacher_id = ?");
   $delete_teacher->execute([$teacher_id]);

   // Redirect back to the teacher list
   header('Location: view_teachers.php');
   exit();
} else {
   echo "No teacher ID provided!";
   exit();
}
?>
