<?php
require_once 'db.php';

echo "Adding urgency_level column to consultations...\n";

// Check if exists
$res = $conn->query("SHOW COLUMNS FROM consultations LIKE 'urgency_level'");
if ($res->num_rows == 0) {
    $sql = "ALTER TABLE consultations ADD COLUMN urgency_level VARCHAR(50) DEFAULT 'routine' AFTER urgency_score";
    if ($conn->query($sql)) {
        echo "✓ Column urgency_level added successfully.\n";
    } else {
        echo "✗ Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "Column urgency_level already exists.\n";
}