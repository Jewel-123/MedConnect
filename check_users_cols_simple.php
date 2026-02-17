<?php
require_once 'db.php';
$result = $conn->query("DESC users");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
