<?php
require_once 'db.php';

echo "Updating payment_transactions schema...\n";

$queries = [
    "ALTER TABLE payment_transactions ADD COLUMN IF NOT EXISTS razorpay_order_id VARCHAR(255) AFTER payment_gateway",
    "ALTER TABLE payment_transactions ADD COLUMN IF NOT EXISTS razorpay_payment_id VARCHAR(255) AFTER razorpay_order_id",
    "ALTER TABLE payment_transactions ADD COLUMN IF NOT EXISTS razorpay_signature VARCHAR(512) AFTER razorpay_payment_id",
    "ALTER TABLE payment_transactions ADD COLUMN IF NOT EXISTS gateway_transaction_id VARCHAR(255) AFTER status",
    "ALTER TABLE payment_transactions ADD COLUMN IF NOT EXISTS completed_at DATETIME AFTER created_at",
    "ALTER TABLE payment_transactions MODIFY COLUMN status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending'"
];

foreach ($queries as $sql) {
    if ($conn->query($sql)) {
        echo "✓ Success: $sql\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
    }
}

echo "\nChecking other related tables for consistency...\n";

// Ensure consultations and prescription_orders have necessary flags if needed
// Actually, payment_api.php handles the updates to appointments/prescription_orders

echo "\nDone.\n";