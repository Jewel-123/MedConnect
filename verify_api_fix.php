<?php
session_start();
$_SESSION['user_id'] = 21; 
$_SESSION['role'] = 'patient';

require_once 'db.php';

$prescriptionId = 16;
$patientId = 21;

// Replicate check_eligibility logic
echo "--- Testing Eligibility Check ---\n";

$stmt = $conn->prepare("
    SELECT po.id, po.order_status, po.payment_status, po.paid_at 
    FROM prescription_orders po
    JOIN prescriptions_v2 p ON po.prescription_id = p.id
    WHERE po.prescription_id = ? AND po.patient_id = ?
    ORDER BY po.created_at DESC LIMIT 1
");
$stmt->bind_param("ii", $prescriptionId, $patientId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo "FAILED: No order found for patient 21.\n";
} else {
    $isPaid = !empty($order['paid_at']) && in_array(strtolower($order['payment_status']), ['paid', 'completed']);
    
    // Aligned logic: check order_status OR prescriptions_v2.status
    $rx_status = $conn->query("SELECT status FROM prescriptions_v2 WHERE id = $prescriptionId")->fetch_assoc()['status'];
    $isCompleted = strtolower($order['order_status']) === 'completed' || strtolower($rx_status) === 'completed';
    
    echo "Is Paid: " . ($isPaid ? "YES" : "NO") . " (" . $order['payment_status'] . ")\n";
    echo "Is Completed: " . ($isCompleted ? "YES" : "NO") . " (Order:" . $order['order_status'] . ", Rx:" . $rx_status . ")\n";
    
    if ($isPaid && $isCompleted) {
        echo "SUCCESS: Prescription #16 is now ELIGIBLE for review in API.\n";
    } else {
        echo "FAILED: Still NOT ELIGIBLE.\n";
    }
}
$conn->close();
?>
