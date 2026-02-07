<?php
require_once 'db.php';

$queries = [
    "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS scheduled_date DATE",
    "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS scheduled_time TIME",
    "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS payment_status VARCHAR(20) DEFAULT 'pending'",
    "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS consultation_fee DECIMAL(10,2) DEFAULT 0.00",
    "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS notes TEXT",
    "ALTER TABLE appointments MODIFY COLUMN status VARCHAR(20) DEFAULT 'booked'"
];

foreach ($queries as $sql) {
    if ($conn->query($sql)) {
        echo "✓ Success: $sql\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
    }
}

echo "Schema update process completed.\n";