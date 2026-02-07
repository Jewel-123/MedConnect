<?php
require_once 'db.php';

echo "Updating payment_transactions schema...\n";

// Add 'appointment' to related_type enum
$query = "ALTER TABLE payment_transactions MODIFY COLUMN related_type ENUM('consultation', 'appointment', 'prescription_order', 'payout')";
if ($conn->query($query)) {
    echo "Successfully updated related_type enum.\n";
} else {
    echo "Error updating related_type enum: " . $conn->error . "\n";
}

// Ensure consultation_fee column exists in consultations table if not already there
// (It should be there based on my schema check, but just in case)
$query = "ALTER TABLE consultations ADD COLUMN IF NOT EXISTS consultation_fee DECIMAL(10,2) DEFAULT 0.00 AFTER doctor_id";
$conn->query($query);

// Check if appointments status needs any update? No, 'booked' and 'pending' are there.

echo "Schema update complete.\n";