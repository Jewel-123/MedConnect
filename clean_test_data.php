<?php
$conn = new mysqli("localhost", "root", "", "medconnect");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

echo "Finding test data for JEWEL BIJU...\n";

$res = $conn->query("
    SELECT a.id, a.reason 
    FROM appointments a 
    JOIN users u ON a.patient_id = u.id 
    WHERE u.full_name = 'JEWEL BIJU'
");

$ids_to_delete = [];
while ($row = $res->fetch_assoc()) {
    echo "Found Appointment ID: {$row['id']} (Reason: {$row['reason']})\n";
    $ids_to_delete[] = $row['id'];
}

if (!empty($ids_to_delete)) {
    $ids_str = implode(',', $ids_to_delete);
    echo "Deleting IDs: $ids_str\n";
    $conn->query("DELETE FROM appointments WHERE id IN ($ids_str)");
    echo "Cleanup complete.\n";
} else {
    echo "No test appointments found for JEWEL BIJU.\n";
}

$conn->close();
?>
