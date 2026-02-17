<?php
require_once 'db.php';

echo "Updating appointments table status ENUM...\n";
$sql = "ALTER TABLE appointments MODIFY COLUMN status ENUM('booked', 'pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'booked'";
if ($conn->query($sql)) {
    echo "Schema updated successfully.\n";
} else {
    echo "Error updating schema: " . $conn->error . "\n";
}

echo "Updating Appointment #68 to 'pending' as it is already 'paid'...\n";
$sql = "UPDATE appointments SET status = 'pending' WHERE id = 68 AND (status = '' OR status IS NULL) AND payment_status = 'paid'";
if ($conn->query($sql)) {
    echo "Appointment #68 updated (affected rows: " . $conn->affected_rows . ").\n";
} else {
    echo "Error updating Appointment #68: " . $conn->error . "\n";
}

$conn->close();
?>
