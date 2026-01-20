<?php
require_once 'db.php';

// Delete the test consultations we created
$result = $conn->query("DELETE FROM consultations WHERE symptoms LIKE '%Test symptoms%' OR symptoms LIKE '%Severe chest pain%' OR symptoms LIKE '%Persistent headache%' OR symptoms LIKE '%Mild cough and runny nose%'");

if ($result) {
    echo "Deleted " . $conn->affected_rows . " test consultations\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

// Verify
$res = $conn->query("SELECT COUNT(*) as count FROM consultations WHERE status = 'pending' AND doctor_id IS NULL");
$count = $res->fetch_assoc()['count'];
echo "Remaining unassigned pending consultations: $count\n";
