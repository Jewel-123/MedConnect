<?php
include 'db.php';

echo "--- Verifying Payment Transactions Table Schema ---\n";

$required_cols = [
    'currency',
    'payment_gateway',
    'razorpay_order_id',
    'razorpay_payment_id',
    'razorpay_signature',
    'gateway_transaction_id',
    'failure_reason',
    'completed_at',
    'related_type'
];

$missing = [];
foreach ($required_cols as $col) {
    $res = $conn->query("SHOW COLUMNS FROM payment_transactions LIKE '$col'");
    if (!$res || $res->num_rows == 0) {
        $missing[] = $col;
    }
}

if (empty($missing)) {
    echo "✅ All required columns are present in payment_transactions table.\n";
} else {
    echo "❌ Missing columns: " . implode(', ', $missing) . "\n";
}

$conn->close();