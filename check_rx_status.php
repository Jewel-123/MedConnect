<?php
require_once 'db.php';
$output = "--- prescriptions_v2 ---\n";
$res = $conn->query("SELECT id, status, total_amount, pharmacy_id FROM prescriptions_v2 ORDER BY created_at DESC LIMIT 5");
while ($row = $res->fetch_assoc()) {
    $output .= "ID: {$row['id']} | Status: {$row['status']} | Amount: {$row['total_amount']} | Pharmacy: {$row['pharmacy_id']}\n";
}

$output .= "\n--- prescription_orders ---\n";
$res = $conn->query("SELECT id, prescription_id, order_status, total_amount, payment_status FROM prescription_orders ORDER BY created_at DESC LIMIT 5");
while ($row = $res->fetch_assoc()) {
    $output .= "ID: {$row['id']} | RX_ID: {$row['prescription_id']} | Status: {$row['order_status']} | Amount: {$row['total_amount']} | PayStatus: {$row['payment_status']}\n";
}
file_put_contents('rx_status_debug.txt', $output);
echo "Output written to rx_status_debug.txt\n";
?>
