<?php
require 'db.php';
$res = $conn->query('DESCRIBE medicines');
$rows = [];
while($row = $res->fetch_assoc()) {
    $rows[] = $row;
}
file_put_contents('medicines_schema.txt', print_r($rows, true));
?>
