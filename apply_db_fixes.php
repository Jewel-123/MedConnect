<?php
require_once 'db.php';

echo "=== Applying Database Fixes ===\n";

// 1. Update status enum for appointments
$sql1 = "ALTER TABLE appointments MODIFY COLUMN status ENUM('booked', 'pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'booked'";
if ($conn->query($sql1)) {
    echo "SUCCESS: Updated appointments status enum.\n";
} else {
    echo "ERROR: Failed to update appointments status enum: " . $conn->error . "\n";
}

// 2. Fix appointments with empty status (set to 'pending' if paid, or 'booked' if unpaid)
$sql2 = "UPDATE appointments SET status = 'pending' WHERE (status = '' OR status IS NULL) AND payment_status = 'paid'";
if ($conn->query($sql2)) {
    echo "SUCCESS: Fixed paid appointments with empty status (" . $conn->affected_rows . " rows).\n";
} else {
    echo "ERROR: Failed to fix paid appointments: " . $conn->error . "\n";
}

$sql3 = "UPDATE appointments SET status = 'booked' WHERE (status = '' OR status IS NULL) AND (payment_status != 'paid' OR payment_status IS NULL)";
if ($conn->query($sql3)) {
    echo "SUCCESS: Fixed unpaid appointments with empty status (" . $conn->affected_rows . " rows).\n";
} else {
    echo "ERROR: Failed to fix unpaid appointments: " . $conn->error . "\n";
}

echo "=== Database Fixes Completed ===\n";
$conn->close();
?>
