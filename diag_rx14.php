<?php
require_once 'db.php';
header('Content-Type: text/plain');

$prescriptionId = 14;
echo "Diagnosing Prescription #$prescriptionId\n";
echo "====================================\n\n";

// 1. Check Prescription Table
$res = $conn->query("SELECT status, payment_status FROM prescriptions_v2 WHERE id = $prescriptionId");
$rx = $res->fetch_assoc();
echo "Prescription Table Status: " . ($rx['status'] ?? 'N/A') . "\n";
echo "Prescription Table Payment: " . ($rx['payment_status'] ?? 'N/A') . "\n\n";

// 2. Check Orders Table
$res = $conn->query("SELECT * FROM prescription_orders WHERE prescription_id = $prescriptionId");
if ($res->num_rows === 0) {
    echo "❌ No order found in prescription_orders for RX $prescriptionId\n";
} else {
    while($order = $res->fetch_assoc()) {
        echo "Order ID: " . $order['id'] . "\n";
        echo "Order Status: " . $order['order_status'] . "\n";
        echo "Payment Status: " . $order['payment_status'] . "\n";
        echo "Paid At: " . ($order['paid_at'] ?? 'NULL') . "\n";
        echo "Completed At: " . ($order['completed_at'] ?? 'NULL') . "\n";
        echo "------------------------------------\n";
    }
}
?>
