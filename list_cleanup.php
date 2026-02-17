<?php
require_once 'db.php';

echo "=== Appointments for JEWEL BIJU (Recent) ===\n";
$res = $conn->query("
    SELECT a.id, a.reason, a.created_at, a.scheduled_date, a.scheduled_time 
    FROM appointments a 
    JOIN users u ON a.patient_id = u.id 
    WHERE u.full_name = 'JEWEL BIJU' 
    ORDER BY a.created_at DESC
");

while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
