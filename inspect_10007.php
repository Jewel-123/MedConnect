<?php
require_once 'db.php';
$r = $conn->query('SELECT status FROM consultations WHERE id=10007')->fetch_assoc();
echo "STATUS: " . $r['status'] . "\n";
?>
