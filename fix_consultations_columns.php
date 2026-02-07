<?php
require_once 'db.php';

echo "=== FIXING CONSULTATIONS TABLE SCHEMA ===\n\n";

function addColumn($conn, $table, $column, $definition, $after = '') {
    $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check && $check->num_rows == 0) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if ($after) $sql .= " AFTER `$after`";
        if ($conn->query($sql)) {
            echo "✓ Added column '$column' to $table\n";
        } else {
            echo "✗ Error adding column '$column': " . $conn->error . "\n";
        }
    } else {
        echo "i Column '$column' already exists in $table\n";
    }
}

// Add missing columns required by symptom_intake_api.php
addColumn($conn, 'consultations', 'input_method', "ENUM('text', 'voice') DEFAULT 'text'", 'symptoms');
addColumn($conn, 'consultations', 'attachment_count', "INT DEFAULT 0", 'input_method');
addColumn($conn, 'consultations', 'urgency_score', "INT DEFAULT 50", 'attachment_count');
addColumn($conn, 'consultations', 'urgency_level', "ENUM('routine', 'priority', 'emergency', 'urgent') DEFAULT 'routine'", 'urgency_score');
addColumn($conn, 'consultations', 'matched_specialty', "VARCHAR(100) DEFAULT NULL", 'urgency_level');
addColumn($conn, 'consultations', 'consultation_mode', "ENUM('video', 'audio', 'text') DEFAULT 'video'", 'matched_specialty');
addColumn($conn, 'consultations', 'language_preference', "VARCHAR(100) DEFAULT 'English'", 'consultation_mode');

// Ensure status ENUM is correct if it exists or add it
addColumn($conn, 'consultations', 'status', "ENUM('pending', 'assigned', 'active', 'completed', 'cancelled') DEFAULT 'pending'", 'language_preference');

echo "\n=== SCHEMA UPDATE COMPLETE ===\n";

$res = $conn->query("DESCRIBE consultations");
echo "\nFinal consultations table structure:\n";
while($row = $res->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}