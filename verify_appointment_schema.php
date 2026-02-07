<?php
include 'db.php';

echo "--- Verifying Appointments Table Schema ---\n";

$required_cols = [
    'scheduled_date',
    'scheduled_time',
    'status',
    'payment_status',
    'consultation_fee',
    'notes',
    'cancellation_reason'
];

$missing = [];
foreach ($required_cols as $col) {
    $res = $conn->query("SHOW COLUMNS FROM appointments LIKE '$col'");
    if (!$res || $res->num_rows == 0) {
        $missing[] = $col;
    }
}

if (empty($missing)) {
    echo "✅ All required columns are present in appointments table.\n";
} else {
    echo "❌ Missing columns: " . implode(', ', $missing) . "\n";
}

$conn->close();