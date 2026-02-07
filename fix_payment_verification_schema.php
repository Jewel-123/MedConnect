<?php
require_once 'db.php';

echo "Updating appointments and prescription_orders schema...\n";

$queries = [
    // Add payment_transaction_id to appointments
    "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS payment_transaction_id INT AFTER payment_status",
    
    // Add payment_transaction_id to prescription_orders
    "ALTER TABLE prescription_orders ADD COLUMN IF NOT EXISTS payment_transaction_id INT AFTER id",
    
    // Add payment_status to prescription_orders (often used in logic)
    "ALTER TABLE prescription_orders ADD COLUMN IF NOT EXISTS payment_status VARCHAR(20) DEFAULT 'pending' AFTER payment_transaction_id"
];

foreach ($queries as $sql) {
    if ($conn->query($sql)) {
        echo "✓ Success: $sql\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
    }
}

echo "\nDone.\n";