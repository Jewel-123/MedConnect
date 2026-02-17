<?php
require_once 'db.php';

// Check Jewel Biju (Patient IDs 21 and 43)
$patient_ids = [21, 43];

foreach ($patient_ids as $pid) {
    echo "=== Data for Patient ID: $pid ===\n";
    $user = $conn->query("SELECT * FROM users WHERE id = $pid")->fetch_assoc();
    if (!$user) {
        echo "User not found.\n\n";
        continue;
    }
    echo "Name: {$user['full_name']} | Email: {$user['email']}\n";

    // Consultations
    $res = $conn->query("SELECT id, status, payment_status, created_at FROM consultations WHERE patient_id = $pid ORDER BY created_at DESC");
    echo "Consultations (" . $res->num_rows . "):\n";
    while($row = $res->fetch_assoc()) {
        echo "  - ID: {$row['id']} | Status: {$row['status']} | Payment: {$row['payment_status']} | Created: {$row['created_at']}\n";
    }

    // Appointments
    $res = $conn->query("SELECT id, status, payment_status, scheduled_date FROM appointments WHERE patient_id = $pid ORDER BY created_at DESC");
    echo "Appointments (" . $res->num_rows . "):\n";
    while($row = $res->fetch_assoc()) {
        echo "  - ID: {$row['id']} | Status: {$row['status']} | Payment: {$row['payment_status']} | Scheduled: {$row['scheduled_date']}\n";
    }
    echo "\n";
}

// Check patient_api.php or similar to see what the dashboard fetches
echo "=== Recent Activity Query Simulation ===\n";
// This is a guess - need to check patient_dashboard.php or JS
?>
