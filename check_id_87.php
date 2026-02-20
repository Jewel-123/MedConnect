<?php
require_once 'db.php';
$appt = $conn->query("SELECT id, notes FROM appointments WHERE id = 87")->fetch_assoc();
$cons = $conn->query("SELECT id, symptoms FROM consultations WHERE id = 87")->fetch_assoc();
echo "Appt 87: " . ($appt ? "EXISTS (Notes: {$appt['notes']})" : "NOT FOUND") . "\n";
echo "Cons 87: " . ($cons ? "EXISTS (Symptoms: {$cons['symptoms']})" : "NOT FOUND") . "\n";
?>
