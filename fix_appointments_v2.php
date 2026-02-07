<?php
include 'db.php';

echo "--- MedConnect Appointment Schema Fix (Transaction Link) ---\n";

$col = "payment_transaction_id";
$def = "INT DEFAULT NULL";

$check = $conn->query("SHOW COLUMNS FROM appointments LIKE '$col'");
if ($check && $check->num_rows == 0) {
    if ($conn->query("ALTER TABLE appointments ADD COLUMN $col $def")) {
        echo "  ✓ Added $col to appointments table\n";
    } else {
        echo "  ! Error adding $col: " . $conn->error . "\n";
    }
} else {
    echo "  - Column $col already exists in appointments table\n";
}

echo "--- Schema Fix Completed ---\n";
$conn->close();