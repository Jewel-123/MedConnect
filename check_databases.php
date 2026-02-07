<?php
include 'db.php';
$result = $conn->query("SHOW DATABASES");
$databases = [];
while($row = $result->fetch_array()) {
    $databases[] = $row[0];
}
echo json_encode($databases, JSON_PRETTY_PRINT);