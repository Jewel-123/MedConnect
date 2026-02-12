<?php
include 'db.php';
$res = $conn->query("SELECT po.*, p.status as rx_status FROM prescription_orders po LEFT JOIN prescriptions_v2 p ON po.prescription_id = p.id");
echo "Rows: " . $res->num_rows . "\n";
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | RX ID: {$row['prescription_id']} | Status: {$row['order_status']} | RX Status: {$row['rx_status']} | Patient: {$row['patient_id']} | Pharmacy: {$row['pharmacy_id']}\n";
}
?>
