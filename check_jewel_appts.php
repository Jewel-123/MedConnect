<?php
require_once 'db.php';
$res = $conn->query("
    SELECT a.*, u.full_name 
    FROM appointments a 
    JOIN users u ON a.patient_id = u.id 
    WHERE u.full_name LIKE '%Jewel%'
");
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Date: " . $row['scheduled_date'] . " | Time: " . $row['scheduled_time'] . " | Notes: [" . $row['notes'] . "]\n";
}
?>
