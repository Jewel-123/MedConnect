<?php
require_once 'db.php';

// Search for ANY consultation created on Feb 18th
$date = '2026-02-18';
$res = $conn->query("SELECT c.*, u.full_name FROM consultations c JOIN users u ON c.patient_id = u.id WHERE DATE(c.created_at) = '$date'");

echo "--- Consultations on $date ---\n";
if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Patient: " . $row['full_name'] . " | Symptoms: " . $row['symptoms'] . " | Created: " . $row['created_at'] . "\n";
    }
} else {
    echo "No consultations found on $date\n";
}

// Also check session table if it exists (unlikely but worth a shot if sessions are DB backed)
// Or check for any 'pending' data in other tables?
?>
