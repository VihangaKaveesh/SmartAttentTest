<?php
// Database credentials
$host = 'localhost'; // Usually 'localhost' if you're running locally
$db_name = 'smartattendtest'; // The name of your database
$username = 'root'; // Your database username
$password = ''; // Your database password

// Create a connection to the MySQL database using MySQLi
$conn = new mysqli($host, $username, $password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Connection was successful
// echo "Connected successfully";
?>
