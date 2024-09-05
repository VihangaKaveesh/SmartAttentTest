<?php

// Include the database connection file
include 'db.php';

// Start a new or resume an existing session
session_start();

// Check if 'user_id' exists in the session and set it, otherwise set it as an empty string
if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
}

// Check if the login form has been submitted
if(isset($_POST['submit'])){

   // Sanitize the 'user_id' input to prevent XSS attacks
   $id = $_POST['user_id'];
   $id = filter_var($id, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

   // Hash the password using sha1 and sanitize it
   $pass = sha1($_POST['pass']);
   $pass = filter_var($pass, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

   // Check if the credentials exist in the 'students' table
   $select_student = $conn->prepare("SELECT * FROM `students` WHERE user_id = ? AND password = ?");
   $select_student->execute([$id, $pass]);
   
   // Check if the credentials exist in the 'lecturer' table
   $select_lecturer = $conn->prepare("SELECT * FROM `lecturer` WHERE lecturer_id = ? AND password = ?");
   $select_lecturer->execute([$id, $pass]);

   // Check if the credentials exist in the 'management' table
   $select_management = $conn->prepare("SELECT * FROM `management` WHERE management_id = ? AND password = ?");
   $select_management->execute([$id, $pass]);

   // If user is a student
   if($select_student->rowCount() > 0){
      // Set the session for the student user
      $row = $select_student->fetch(PDO::FETCH_ASSOC);
      $_SESSION['user_id'] = $row['id'];
      // Redirect to the student dashboard
      header('location:studentDashboard.php');
   }
   // If user is a lecturer
   elseif($select_lecturer->rowCount() > 0){
      // Set the session for the lecturer user
      $row = $select_lecturer->fetch(PDO::FETCH_ASSOC);
      $_SESSION['user_id'] = $row['id'];
      // Redirect to the lecturer dashboard
      header('location:lecturerDashboard.php');
   }
   // If user is management
   elseif($select_management->rowCount() > 0){
      // Set the session for the management user
      $row = $select_management->fetch(PDO::FETCH_ASSOC);
      $_SESSION['user_id'] = $row['id'];
      // Redirect to the management dashboard
      header('location:managementDashboard.php');
   }
   // If credentials do not match any user
   else{
      $message[] = 'Incorrect ID or Password!';
   }
}

?>
