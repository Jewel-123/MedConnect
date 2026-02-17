<?php
require_once 'db.php';

try {
    $conn->query("ALTER TABLE prescriptions_v2 ADD COLUMN auto_sent_to_pharmacy BOOLEAN DEFAULT FALSE");
    echo "Column 'auto_sent_to_pharmacy' added successfully to prescriptions_v2 table.";
} catch (Exception $e) {
    echo "Error adding column: " . $e->getMessage();
}

$conn->close();
?>
