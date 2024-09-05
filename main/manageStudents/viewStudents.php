<?php

// Include database connection
include 'db.php';

// Start session
session_start();

// Fetch all student details from the `students` table
$select_students = $conn->prepare("SELECT * FROM `students`");
$select_students->execute();
$students = $select_students->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>View Students</title>
   <style>
      table {
         width: 100%;
         border-collapse: collapse;
      }
      table, th, td {
         border: 1px solid black;
         padding: 10px;
         text-align: left;
      }
   </style>
</head>
<body>

<h2>Student Details</h2>

<!-- Table to display student details -->
<table>
   <thead>
      <tr>
         <th>Student ID</th>
         <th>Username</th>
         <th>Name</th>
         <th>Email</th>
         <th>Phone Number</th>
         <th>Course</th>
         <th>Actions</th>
      </tr>
   </thead>
   <tbody>
      <?php foreach($students as $student): ?>
         <tr>
            <td><?= $student['student_id']; ?></td>
            <td><?= $student['username']; ?></td>
            <td><?= $student['name']; ?></td>
            <td><?= $student['email']; ?></td>
            <td><?= $student['phone_number']; ?></td>
            <td><?= $student['course']; ?></td>
            <td>
               <!-- Update button -->
               <a href="update_student.php?id=<?= $student['student_id']; ?>">Update</a> |
               <!-- Delete button -->
               <a href="delete_student.php?id=<?= $student['student_id']; ?>" onclick="return confirm('Are you sure you want to delete this student?')">Delete</a>
            </td>
         </tr>
      <?php endforeach; ?>
   </tbody>
</table>

</body>
</html>
