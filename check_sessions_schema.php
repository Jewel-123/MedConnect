<?php
require_once 'db.php';
$res = $conn->query('DESCRIBE consultation_sessions');
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
