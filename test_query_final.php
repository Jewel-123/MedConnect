<?php
include 'db.php';
$prescriptionId = 6;
$userId = 21;

$stmt = $conn->prepare("
    SELECT po.*, p.prescription_number, p.diagnosis,
           u.full_name as pharmacy_name,
           pp.pharmacy_name as pharmacy_business_name,
           pat.full_name as patient_name,
           pat.email as patient_email,
           pat.phone as patient_phone
    FROM prescription_orders po
    JOIN prescriptions_v2 p ON po.prescription_id = p.id
    JOIN users u ON po.pharmacy_id = u.id
    LEFT JOIN pharmacy_profiles pp ON u.id = pp.user_id
    JOIN users pat ON po.patient_id = pat.id
    WHERE po.prescription_id = ? AND po.patient_id = ?
");
$stmt->bind_param("ii", $prescriptionId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if ($order) {
    echo "SUCCESS: Order found!\n";
    print_r($order);
} else {
    echo "FAILURE: Order NOT found!\n";
    
    // Debug parts
    $res = $conn->query("SELECT * FROM prescription_orders WHERE prescription_id = $prescriptionId AND patient_id = $userId");
    echo "PO check: " . $res->num_rows . "\n";
    
    $res = $conn->query("SELECT * FROM prescriptions_v2 WHERE id = $prescriptionId");
    echo "Rx check: " . $res->num_rows . "\n";
    
    $res = $conn->query("SELECT * FROM users WHERE id = 4");
    echo "Pharmacy user check: " . $res->num_rows . "\n";
    
    $res = $conn->query("SELECT * FROM users WHERE id = $userId");
    echo "Patient user check: " . $res->num_rows . "\n";
}
?>
