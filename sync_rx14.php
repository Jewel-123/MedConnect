<?php
require_once 'db.php';
header('Content-Type: text/plain');

echo "Syncing statuses from prescriptions_v2 to prescription_orders...\n";

$query = "
    UPDATE prescription_orders po
    JOIN prescriptions_v2 p ON po.prescription_id = p.id
    SET po.payment_status = CASE 
        WHEN p.payment_status = 'Paid' THEN 'Paid'
        ELSE po.payment_status 
    END,
    po.order_status = CASE 
        WHEN p.status = 'Completed' THEN 'completed'
        WHEN p.status = 'Dispensed' THEN 'completed'
        ELSE po.order_status 
    END,
    po.paid_at = CASE 
        WHEN p.paid_at IS NOT NULL THEN p.paid_at
        WHEN p.payment_status = 'Paid' THEN p.created_at
        ELSE po.paid_at 
    END,
    po.completed_at = CASE 
        WHEN p.status = 'Completed' THEN p.created_at
        ELSE po.completed_at 
    END
    WHERE po.prescription_id = 14
";

if ($conn->query($query)) {
    echo "✅ Successfully synced statuses for Prescription #14\n";
    echo "Rows affected: " . $conn->affected_rows . "\n";
} else {
    echo "❌ Sync failed: " . $conn->error . "\n";
}
?>
