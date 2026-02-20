<?php
require_once 'db.php';
$res = $conn->query("SHOW COLUMNS FROM consultations LIKE 'appointment_id'");
if ($res->num_rows === 0) {
    echo "Adding appointment_id column to consultations table...\n";
    $conn->query("ALTER TABLE consultations ADD COLUMN appointment_id INT(11) DEFAULT NULL AFTER id");
    $conn->query("ALTER TABLE consultations ADD INDEX (appointment_id)");
    echo "Column added successfully.\n";
} else {
    echo "appointment_id column already exists.\n";
}
?>
