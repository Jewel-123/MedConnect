<?php
include 'db.php';
$result = $conn->query("DESCRIBE users");
$columns = [];
while($row = $result->fetch_assoc()) {
    $columns[] = $row;
}
echo json_encode($columns, JSON_PRETTY_PRINT);