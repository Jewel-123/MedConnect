<?php
// Debug the foreign key error
require_once 'db.php';

echo "=== Investigating Foreign Key Error ===\n\n";

// Check recent payment transactions
echo "Recent payment transactions:\n";
$payments = $conn->query("
    SELECT id, transaction_number, related_id, doctor_id, status, created_at
    FROM payment_transactions
    ORDER BY created_at DESC
    LIMIT 5
");

while ($row = $payments->fetch_assoc()) {
    echo "  Payment #{$row['id']}: related_id={$row['related_id']}, doctor_id={$row['doctor_id']}, status={$row['status']}\n";
    
    // Check if consultation exists
    if ($row['related_id']) {
        $consult = $conn->query("SELECT id FROM consultations WHERE id = {$row['related_id']}")->fetch_assoc();
        if ($consult) {
            echo "    ✅ Consultation #{$row['related_id']} EXISTS\n";
        } else {
            echo "    ❌ Consultation #{$row['related_id']} NOT FOUND!\n";
        }
    }
    
    // Check if earning record exists
    $earning = $conn->query("SELECT id FROM doctor_earnings WHERE consultation_id = {$row['related_id']}")->fetch_assoc();
    if ($earning) {
        echo "    ✅ Earning record exists\n";
    } else {
        echo "    ⚠️ No earning record\n";
    }
}

// Check for orphaned earnings (earnings with no consultation)
echo "\n=== Checking for orphaned earnings ===\n";
$orphaned = $conn->query("
    SELECT de.id, de.consultation_id, de.doctor_id
    FROM doctor_earnings de
    LEFT JOIN consultations c ON de.consultation_id = c.id
    WHERE c.id IS NULL
");

if ($orphaned && $orphaned->num_rows > 0) {
    echo "Found {$orphaned->num_rows} orphaned earning record(s):\n";
    while ($row = $orphaned->fetch_assoc()) {
        echo "  Earning #{$row['id']}: consultation_id={$row['consultation_id']} (doesn't exist)\n";
    }
} else {
    echo "No orphaned earning records\n";
}
