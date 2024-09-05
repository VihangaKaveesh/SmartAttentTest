<?php
// Include the database connection
include 'db.php'; // This file contains the code to connect to your database

// Fetch all teachers from the database
$select_teachers = $conn->prepare("SELECT * FROM `teachers`"); // Prepare SQL query to fetch all teachers
$select_teachers->execute(); // Execute the query
$teachers = $select_teachers->fetchAll(PDO::FETCH_ASSOC); // Fetch all results as an associative array
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>View Teachers</title> <!-- Page title -->
</head>
<body>

<h2>List of Teachers</h2> <!-- Heading for the list of teachers -->

<!-- Check if there are any teachers to display -->
<?php if(count($teachers) > 0): ?>
   <table> <!-- Start table with a border -->
      <tr>
         <!-- Table headers for each column -->
         <th>ID</th>
         <th>Username</th>
         <th>Name</th>
         <th>Email</th>
         <th>Phone Number</th>
         <th>Department</th>
         <th>Actions</th> <!-- Action column for Update and Delete links -->
      </tr>

      <!-- Loop through each teacher in the array and create a table row for each -->
      <?php foreach($teachers as $teacher): ?>
      <tr>
         <td><?= $teacher['teacher_id']; ?></td> <!-- Display teacher ID -->
         <td><?= $teacher['username']; ?></td> <!-- Display teacher username -->
         <td><?= $teacher['name']; ?></td> <!-- Display teacher name -->
         <td><?= $teacher['email']; ?></td> <!-- Display teacher email -->
         <td><?= $teacher['phone_number']; ?></td> <!-- Display teacher phone number -->
         <td><?= $teacher['department']; ?></td> <!-- Display teacher department -->
         <td>
            <!-- Update link that passes the teacher ID in the URL -->
            <a href="update_teacher.php?id=<?= $teacher['teacher_id']; ?>">Update</a>
            <!-- Delete link that also passes the teacher ID and includes a confirmation prompt -->
            <a href="delete_teacher.php?id=<?= $teacher['teacher_id']; ?>" onclick="return confirm('Are you sure you want to delete this teacher?');">Delete</a>
         </td>
      </tr>
      <?php endforeach; ?>
   </table>
<?php else: ?>
   <!-- If no teachers are found, display a message -->
   <p>No teachers found.</p>
<?php endif; ?>

</body>
</html>
