<?php
require_once 'db.php';
$res = $conn->query("
    SELECT a.*, u.full_name 
    FROM appointments a 
    JOIN users u ON a.patient_id = u.id 
    WHERE a.scheduled_time LIKE '%10:22%' OR a.scheduled_date = '2026-02-24'
");
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Name: " . $row['full_name'] . " | Date: " . $row['scheduled_date'] . " | Time: " . $row['scheduled_time'] . " | Notes: [" . $row['notes'] . "]\n";
}
?>
