<?php
require_once 'db.php';
$res = $conn->query("SHOW COLUMNS FROM consultation_sessions");
$cols = [];
while($row = $res->fetch_assoc()) {
    $cols[] = $row;
}
file_put_contents('debug_session_schema.txt', print_r($cols, true));
echo "Schema saved to debug_session_schema.txt\n";
?>
