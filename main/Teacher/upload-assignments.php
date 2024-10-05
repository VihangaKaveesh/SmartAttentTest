<?php

function connectDB() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "smartattendtest";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

//Check if file is uploaded 
if (isset($_POST['submit'])) {
    $targetDir = "uploads/";
    $targetFile = $targetDir . basename($_FILES["pdfFile"]["name"]);
    $fileType = strtolower(pathinfo($targetFile,PATHINFO_EXTENSION));

    //Check if file is a PDF and less than 
}