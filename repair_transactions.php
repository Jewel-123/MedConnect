<?php
require_once 'db.php';

echo "Repairing transaction records...\n";

// Fix missing related_type
$query = "UPDATE payment_transactions SET related_type = 'appointment' WHERE transaction_type = 'consultation_fee' AND (related_type = '' OR related_type IS NULL)";
$conn->query($query);
echo "Fixed " . $conn->affected_rows . " records with missing related_type.\n";

echo "Repair complete.\n";