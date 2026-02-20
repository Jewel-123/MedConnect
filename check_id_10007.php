<?php
require_once 'db.php';
$appt = $conn->query("SELECT id FROM appointments WHERE id = 10007")->fetch_assoc();
$cons = $conn->query("SELECT id FROM consultations WHERE id = 10007")->fetch_assoc();
echo "Appt 10007: " . ($appt ? "EXISTS" : "NOT FOUND") . "\n";
echo "Cons 10007: " . ($cons ? "EXISTS" : "NOT FOUND") . "\n";
?>
