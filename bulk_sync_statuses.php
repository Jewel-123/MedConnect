<?php
require_once 'db.php';
header('Content-Type: text/plain');

echo "Bulk Syncing Legacy Prescription Statuses...\n";
echo "===========================================\n\n";

$query = "
    UPDATE prescription_orders po
    JOIN prescriptions_v2 p ON po.prescription_id = p.id
    SET po.payment_status = CASE 
        WHEN p.payment_status = 'Paid' THEN 'Paid'
        ELSE po.payment_status 
    END,
    po.order_status = CASE 
        WHEN (p.status = 'Completed' OR p.status = 'Dispensed') THEN 'completed'
        ELSE po.order_status 
    END,
    po.paid_at = CASE 
        WHEN po.paid_at IS NULL AND p.paid_at IS NOT NULL THEN p.paid_at
        WHEN po.paid_at IS NULL AND p.payment_status = 'Paid' THEN p.created_at
        ELSE po.paid_at 
    END,
    po.completed_at = CASE 
        WHEN po.completed_at IS NULL AND (p.status = 'Completed' OR p.status = 'Dispensed') THEN p.created_at
        ELSE po.completed_at 
    END
    WHERE (p.payment_status = 'Paid' AND (po.payment_status != 'Paid' OR po.paid_at IS NULL))
       OR ((p.status = 'Completed' OR p.status = 'Dispensed') AND (po.order_status != 'completed' OR po.completed_at IS NULL))
";

if ($conn->query($query)) {
    echo "✅ Success: Bulk sync complete.\n";
    echo "Total orders fixed: " . $conn->affected_rows . "\n";
} else {
    echo "❌ Error: " . $conn->error . "\n";
}

// Final check for review_submitted flag
$conn->query("UPDATE prescription_orders po JOIN prescription_reviews pr ON po.id = pr.prescription_order_id SET po.review_submitted = 1");

echo "\nSynchronization finished.\n";
?>
