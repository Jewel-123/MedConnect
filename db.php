<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "medconnect";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database Connection Failed: " . $conn->connect_error]));
}

$conn->query("SET time_zone = '+05:30'");
?>
