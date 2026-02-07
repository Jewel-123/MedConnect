<?php
require_once 'db.php';

echo "Cleaning up existing records...\n";

// 1. Find 'booked' appointments that have a completed transaction
$query = "
    UPDATE appointments a
    JOIN payment_transactions pt ON a.id = pt.related_id
    SET a.status = 'pending', a.payment_status = 'paid', a.payment_transaction_id = pt.id
    WHERE a.status = 'booked' AND pt.status = 'completed' AND pt.transaction_type = 'consultation_fee'
";
$conn->query($query);
echo "Updated " . $conn->affected_rows . " appointments from 'booked' to 'pending' based on completed payments.\n";

// 2. Fix missing doctor_id in payment_transactions for existing records
$query = "
    UPDATE payment_transactions pt
    JOIN appointments a ON pt.related_id = a.id
    SET pt.doctor_id = a.doctor_id
    WHERE pt.transaction_type = 'consultation_fee' AND pt.doctor_id IS NULL
";
$conn->query($query);
echo "Updated " . $conn->affected_rows . " transactions with missing doctor_id from appointments.\n";

$query = "
    UPDATE payment_transactions pt
    JOIN consultations c ON pt.related_id = c.id
    SET pt.doctor_id = c.doctor_id
    WHERE pt.transaction_type = 'consultation_fee' AND pt.doctor_id IS NULL
";
$conn->query($query);
echo "Updated " . $conn->affected_rows . " transactions with missing doctor_id from consultations.\n";

echo "Cleanup complete.\n";