<?php
$servername = "127.0.0.1"; // Forced IPv4
$username = "root";
$password = "";

$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully to MySQL server via 127.0.0.1";
?>
