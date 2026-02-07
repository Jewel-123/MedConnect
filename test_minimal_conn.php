<?php
$conn = new mysqli("127.0.0.1", "root", "", "medconnect");
if ($conn->connect_error) {
    echo "Connection error: " . $conn->connect_error;
} else {
    echo "Connection successful!";
}