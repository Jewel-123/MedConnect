<?php
session_start();
require_once 'db.php';

// Check count
$result = $conn->query("SELECT COUNT(*) as count FROM consultation_sessions WHERE consultation_id = 103");
$count = $result->fetch_assoc()['count'];
echo "Total Sessions (Expected 1): " . $count . "\n";
?>
