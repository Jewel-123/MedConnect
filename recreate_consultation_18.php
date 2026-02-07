<?php
// Recreate the deleted consultation for Sophia Martinez
require_once 'db.php';

echo "=== Recreating Consultation #18 for Sophia Martinez ===\n\n";

// Get payment transaction details
$payment = $conn->query("
    SELECT * FROM payment_transactions 
    WHERE id = 29
")->fetch_assoc();

if (!$payment) {
    die("Payment transaction #29 not found!\n");
}

echo "Payment Transaction:\n";
echo "  ID: {$payment['id']}\n";
echo "  User: {$payment['user_id']}\n";
echo "  Doctor: {$payment['doctor_id']}\n";
echo "  Amount: {$payment['amount']}\n";
echo "  Status: {$payment['status']}\n\n";

// Get user details to find symptoms
$user = $conn->query("SELECT full_name FROM users WHERE id = {$payment['user_id']}")->fetch_assoc();

// Recreate consultation
$stmt = $conn->prepare("
    INSERT INTO consultations (
        id, patient_id, doctor_id, consultation_mode, consultation_fee,
        symptoms, severity, urgency_score,
        language_preference, status, payment_status, payment_transaction_id, created_at
    ) VALUES (18, ?, ?, 'video', ?, 'Skin irritation and rash', 'medium', 60, 'English', 'pending', 'paid', ?, NOW())
");

$patient_id = $payment['user_id'];
$doctor_id = $payment['doctor_id'];
$amount = $payment['amount'];
$transaction_id = $payment['id'];

$stmt->bind_param("iidi", $patient_id, $doctor_id, $amount, $transaction_id);

if ($stmt->execute()) {
    echo "✅ Successfully recreated consultation #18\n";
    
    // Verify it exists
    $verify = $conn->query("
        SELECT c.id, c.patient_id, c.doctor_id, u.full_name as patient, c.status, 
               c.payment_status, c.consultation_fee
        FROM consultations c
        JOIN users u ON c.patient_id = u.id
        WHERE c.id = 18
    ")->fetch_assoc();
    
    echo "\nVerification:\n";
    echo "  ID: {$verify['id']}\n";
    echo "  Patient: {$verify['patient']}\n";
    echo "  Doctor ID: {$verify['doctor_id']}\n";
    echo "  Status: {$verify['status']}\n";
    echo "  Payment: {$verify['payment_status']}\n";
    echo "  Fee: {$verify['consultation_fee']}\n\n";
    
    echo "✅ This consultation should now appear in Dr. Sophia Martinez's incoming requests!\n";
} else {
    echo "❌ Error: " . $stmt->error . "\n";
}
