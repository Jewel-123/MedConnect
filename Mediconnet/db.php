<?php
$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = "";     // Default XAMPP password (empty)
$dbname = "medconnect";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database Connection Failed: " . $conn->connect_error]));
}
?>
