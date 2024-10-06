<?php
session_start();

// Check if the user is logged in as a student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login/login.html");
    exit();
}

// Function to connect to the database
function connectDB() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "smartattendtest";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

// Connect to the database
$conn = connectDB();

// Fetch all notices from the notice_board table
$sqlNotices = "SELECT noticeName, filename, folder_path 
               FROM notice_board";
$resultNotices = $conn->query($sqlNotices);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Notice Board</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--Bootstrap CSS-->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h2 class="mt-4">Notices</h2>
        <?php
        if ($resultNotices->num_rows > 0) {
            echo "<table class='table table-bordered'>";
            echo "<thead><tr><th>Notice Name</th><th>Download</th></tr></thead>";
            echo "<tbody>";

            // Loop through each notice and display it
            while ($rowNotice = $resultNotices->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($rowNotice['noticeName']) . "</td>";

                // Download link for the notice
                echo "<td><a href='../Management/notices/" . htmlspecialchars($rowNotice['filename']) . "' download class='btn btn-success'>Download</a></td>";

                echo "</tr>";
            }

            echo "</tbody></table>";
        } else {
            echo "<p>No notices are available at the moment.</p>";
        }

        $conn->close(); // Close the database connection
        ?>
    </div>

    <!--Bootstrap JS-->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
</body>
</html>
