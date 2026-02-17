<?php
require_once 'db.php';
header('Content-Type: text/plain');

$queries = [
    "ALTER TABLE prescription_orders ADD COLUMN IF NOT EXISTS payment_status ENUM('pending', 'Paid', 'completed', 'Failed') DEFAULT 'pending' AFTER pharmacy_id",
    "ALTER TABLE prescription_orders ADD COLUMN IF NOT EXISTS order_status ENUM('pending', 'accepted', 'preparing', 'in_progress', 'ready', 'out_for_delivery', 'delivered', 'completed', 'cancelled', 'dispensed') DEFAULT 'pending' AFTER payment_status",
    "ALTER TABLE prescription_orders ADD COLUMN IF NOT EXISTS payment_transaction_id VARCHAR(255) AFTER order_status",
    "ALTER TABLE prescription_orders ADD COLUMN IF NOT EXISTS review_submitted BOOLEAN DEFAULT FALSE AFTER payment_transaction_id",
    "ALTER TABLE prescription_orders ADD COLUMN IF NOT EXISTS paid_at TIMESTAMP NULL DEFAULT NULL AFTER review_submitted",
    "ALTER TABLE prescription_orders ADD COLUMN IF NOT EXISTS completed_at TIMESTAMP NULL DEFAULT NULL AFTER paid_at"
];

foreach ($queries as $query) {
    try {
        if ($conn->query($query)) {
            echo "✅ Success: " . substr($query, 0, 50) . "...\n";
        }
    } catch (Exception $e) {
        // If IF NOT EXISTS is not supported or column exists, just report error but continue
        echo "❌ Info/Error: " . $e->getMessage() . "\n";
    }
}

// Also update the enum if already exists but missing 'completed'
$conn->query("ALTER TABLE prescription_orders MODIFY COLUMN order_status ENUM('pending', 'accepted', 'preparing', 'in_progress', 'ready', 'out_for_delivery', 'delivered', 'completed', 'cancelled', 'dispensed') DEFAULT 'pending'");

echo "\nSchema update complete.\n";
?>
