<?php
require_once 'db.php';

echo "=== ALIGNING DOCTOR PROFILES TABLE ===\n\n";

function addColumnIfNotExists($conn, $table, $column, $definition, $after = '') {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($result && $result->num_rows == 0) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if ($after) $sql .= " AFTER `$after`";
        if ($conn->query($sql)) {
            echo "✓ Added column $column to $table\n";
        } else {
            echo "✗ Error adding column $column: " . $conn->error . "\n";
        }
    } else {
        echo "i Column $column already exists in $table\n";
    }
}

addColumnIfNotExists($conn, 'doctor_profiles', 'years_experience', 'INT DEFAULT 0', 'license_number');
addColumnIfNotExists($conn, 'doctor_profiles', 'languages_spoken', 'VARCHAR(255) DEFAULT \'English\'', 'consultation_fee');

echo "\n=== ALIGNMENT COMPLETE ===\n";
?>
