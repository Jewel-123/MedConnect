<?php
require_once 'db.php';

// Fix Appointment 55
$stmt = $conn->prepare("UPDATE appointments SET status = 'pending' WHERE id = 55 AND status = ''");
if ($stmt->execute()) {
    echo "Updated Appointment 55 status to 'pending'.\n";
} else {
    echo "Failed to update Appointment 55: " . $stmt->error . "\n";
}

// Check for other empty status appointments
$sql = "SELECT id, patient_id, doctor_id, created_at FROM appointments WHERE status = '' AND payment_status = 'paid'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    echo "Found other appointments with empty status:\n";
    while($row = $result->fetch_assoc()) {
        print_r($row);
        // Fix them too?
        $conn->query("UPDATE appointments SET status = 'pending' WHERE id = " . $row['id']);
        echo "Fixed Appointment " . $row['id'] . "\n";
    }
} else {
    echo "No other paid appointments with empty status found.\n";
}
?>
