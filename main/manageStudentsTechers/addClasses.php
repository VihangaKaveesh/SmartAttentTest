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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_class"])) {
    $class_name = filter_var($_POST["class_name"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if (!empty($class_name)) {
        $conn = connectDB();
        
        $stmt = $conn->prepare("INSERT INTO classes (class_name) VALUES (?)");
        $stmt->bind_param("s", $class_name);

        if ($stmt->execute()) {
            echo "<p class='success'>Class added successfully.</p>";
        } else {
            echo "<p class='error'>Error adding class: " . $stmt->error . "</p>";
        }

        $stmt->close();
        $conn->close();
    } else {
        echo "<p class='error'>Class name cannot be empty.</p>";
    }
}
?>

<!-- Form to add a new class -->
<form action="" method="post">
    <label for="class_name">Class Name:</label>
    <input type="text" id="class_name" name="class_name" required>
    <input type="submit" name="add_class" value="Add Class">
</form>
