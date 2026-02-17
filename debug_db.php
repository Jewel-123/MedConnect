<?php
require_once 'db.php';
$prescription_id = 16;
$sql = "SELECT patient_id FROM prescription_orders WHERE prescription_id = $prescription_id";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) {
    echo "PATIENT_ID:" . $row['patient_id'] . "\n";
}
$conn->close();
?>
