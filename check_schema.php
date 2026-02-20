<?php
require_once 'db.php';
$fp = fopen('schema_dump.txt', 'w');
$result = $conn->query("SHOW COLUMNS FROM consultation_sessions");
while ($row = $result->fetch_assoc()) {
    fwrite($fp, print_r($row, true) . "\n");
}
fclose($fp);
echo "Schema dumped to schema_dump.txt";
?>
