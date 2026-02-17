<?php
require_once 'db.php';

echo "--- Searching for Emily Smith ---\n";
$result = $conn->query("SELECT id, name, role FROM users WHERE name LIKE '%Emily%'");
$emily_id = null;
while ($row = $result->fetch_assoc()) {
    print_r($row);
    if (strpos($row['name'], 'Emily Smith') !== false) {
        $emily_id = $row['id'];
    }
}

echo "\n--- Most recent consultations ---\n";
$result = $conn->query("SELECT * FROM consultations ORDER BY id DESC LIMIT 10");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Patient: " . $row['patient_id'] . " | Doctor: " . $row['doctor_id'] . " | Symptoms: " . ($row['symptoms'] ?? 'N/A') . " | Reason: " . ($row['reason'] ?? 'N/A') . " | Status: " . $row['status'] . " | Created: " . $row['created_at'] . "\n";
        // Print full row if it looks like the one we want
        if (stripos(($row['symptoms'] ?? ''), 'fever') !== false || stripos(($row['reason'] ?? ''), 'fever') !== false) {
            print_r($row);
        }
    }
}

echo "\n--- Most recent appointments ---\n";
$result = $conn->query("SELECT * FROM appointments ORDER BY id DESC LIMIT 10");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Patient: " . $row['patient_id'] . " | Doctor: " . $row['doctor_id'] . " | Reason: " . ($row['reason'] ?? 'N/A') . " | Status: " . $row['status'] . " | Created: " . $row['created_at'] . "\n";
        if (stripos(($row['reason'] ?? ''), 'fever') !== false) {
            print_r($row);
        }
    }
}
?>
