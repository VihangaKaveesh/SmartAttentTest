<?php

// Function to connect to the database
function connectDB() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "smartattendtest";

    // Create a new connection object
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check if connection is successful
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Return the connection object
    return $conn;
}

// Call connectDB function to get the connection
$conn = connectDB();

// Query to select all files from the assignments table
$sql = "SELECT AssignmentName, filename, HandOutDate, DueDate FROM assignments"; // Adjust the fields as needed
$result = $conn->query($sql);

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Download PDF</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
    integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>
<body>
    <div class="container">
        <h1 class="mt-4">Download Assignments</h1>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>Assignment Name</th>
                        <th>Hand Out Date</th>
                        <th>Due Date</th>
                        <th>Download</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $count = 1;
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>".$row['AssignmentName']."</td>"; // Display assignment name
                            echo "<td>".$row['HandOutDate']."</td>"; // Display hand out date
                            echo "<td>".$row['DueDate']."</td>"; // Display due date
                            echo "<td><a href='../Teacher/uploads/".$row['filename']."' download>Download</a></td>"; // Download link
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3'>No records found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
