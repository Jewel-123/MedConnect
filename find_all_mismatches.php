<?php
require_once 'db.php';
header('Content-Type: text/plain');

echo "Finding Status Mismatches (prescriptions_v2 vs prescription_orders)\n";
echo "===============================================================\n\n";

$query = "
    SELECT p.id as rx_id, p.status as rx_status, p.payment_status as rx_payment, 
           po.id as order_id, po.order_status, po.payment_status as po_payment,
           u.full_name as patient_name
    FROM prescriptions_v2 p
    JOIN prescription_orders po ON p.id = po.prescription_id
    JOIN users u ON p.patient_id = u.id
    WHERE (p.payment_status = 'Paid' AND (po.payment_status != 'Paid' OR po.paid_at IS NULL))
       OR ((p.status = 'Completed' OR p.status = 'Dispensed') AND (po.order_status != 'completed' OR po.completed_at IS NULL))
";

$res = $conn->query($query);
if ($res->num_rows === 0) {
    echo "No obvious mismatches found.\n";
} else {
    echo "Summary of Mismatches:\n";
    echo "RX ID | Patient      | RX Status | RX Pay | Order Status | PO Pay\n";
    echo "------|--------------|-----------|--------|--------------|--------\n";
    while($row = $res->fetch_assoc()) {
        printf("%-5d | %-12s | %-9s | %-6s | %-12s | %-6s\n", 
            $row['rx_id'], 
            substr($row['patient_name'], 0, 12),
            $row['rx_status'],
            $row['rx_payment'],
            $row['order_status'],
            $row['po_payment']
        );
    }
}
?>
