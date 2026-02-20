<?php
require_once 'db.php';
$res = $conn->query('DESCRIBE appointments');
$output = "";
while($row = $res->fetch_assoc()) {
    $output .= $row['Field'] . ' ' . $row['Type'] . "\n";
}
file_put_contents('schema_output.txt', $output);
?>
