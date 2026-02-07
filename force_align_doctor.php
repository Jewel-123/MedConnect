<?php
require_once 'db.php';

echo "=== FORCING ALIGNMENT OF DOCTOR PROFILES ===\n";

$sql = "ALTER TABLE doctor_profiles 
        ADD COLUMN IF NOT EXISTS years_experience INT DEFAULT 0 AFTER license_number,
        ADD COLUMN IF NOT EXISTS languages_spoken VARCHAR(255) DEFAULT 'English' AFTER consultation_fee";

if ($conn->query($sql)) {
    echo "✓ Alignment SQL executed successfully\n";
} else {
    echo "✗ Error aligning table: " . $conn->error . "\n";
}

$res = $conn->query("DESCRIBE doctor_profiles");
echo "\nFinal Columns:\n";
while($row = $res->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}