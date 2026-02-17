<?php
require_once 'db.php';

$prescriptionId = 16;
$userId = 1; // Assuming patient_id 1 based on previous debug info (usually patients have low IDs in dev)

// Replicate the logic from prescription_review.php
$stmt = $conn->prepare("
    SELECT po.*, p.prescription_number,
           u.full_name as pharmacy_name
    FROM prescription_orders po
    JOIN prescriptions_v2 p ON po.prescription_id = p.id
    JOIN users u ON po.pharmacy_id = u.id
    WHERE po.prescription_id = ? 
    AND (po.order_status = 'completed' OR p.status = 'completed')
    AND po.paid_at IS NOT NULL
    AND (LOWER(po.payment_status) = 'paid' OR LOWER(po.payment_status) = 'completed')
");
$stmt->bind_param("i", $prescriptionId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if ($order) {
    echo "SUCCESS: Fix verified. Prescription #$prescriptionId is now eligible for review.\n";
    echo "Order Number: " . $order['order_number'] . "\n";
    echo "Payment Status: " . $order['payment_status'] . "\n";
} else {
    echo "FAILURE: Fix verification failed. Prescription #$prescriptionId still not found in query.\n";
}
$conn->close();
?>
