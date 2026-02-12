<?php
include 'db.php';
$res = $conn->query("SELECT id, status, bill_generated_at, total_amount, pharmacy_id, patient_id FROM prescriptions_v2 WHERE id = 6");
$rx = $res->fetch_assoc();
echo "--- prescriptions_v2 (ID 6) ---\n";
foreach($rx as $k => $v) echo "$k: $v\n";

$res = $conn->query("SELECT id, order_status, total_amount, pharmacy_id, patient_id FROM prescription_orders WHERE prescription_id = 6");
$po = $res->fetch_assoc();
echo "\n--- prescription_orders (RX 6) ---\n";
if ($po) {
    foreach($po as $k => $v) echo "$k: $v\n";
} else {
    echo "No order found\n";
}
?>
