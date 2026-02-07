<?php
/**
 * Cleanup script for unassigned consultations
 * Handles consultations with NULL doctor_id
 */

require_once 'db.php';

echo "=== UNASSIGNED CONSULTATIONS CLEANUP ===\n\n";

// Find all consultations with NULL doctor_id
$unassigned = $conn->query("
    SELECT c.*, u.full_name as patient_name, u.email as patient_email,
           pt.amount as payment_amount, pt.id as transaction_id
    FROM consultations c
    JOIN users u ON c.patient_id = u.id
    LEFT JOIN payment_transactions pt ON pt.related_id = c.id 
        AND pt.transaction_type = 'consultation_fee'
    WHERE c.doctor_id IS NULL
    ORDER BY c.created_at DESC
");

$count = $unassigned->num_rows;

if ($count == 0) {
    echo "✅ No unassigned consultations found. All consultations have assigned doctors.\n";
    exit(0);
}

echo "Found $count unassigned consultation(s):\n\n";

$consultations = [];
while ($row = $unassigned->fetch_assoc()) {
    $consultations[] = $row;
    echo "ID: {$row['id']}\n";
    echo "  Patient: {$row['patient_name']} ({$row['patient_email']})\n";
    echo "  Status: {$row['status']}\n";
    echo "  Payment Status: {$row['payment_status']}\n";
    echo "  Payment Amount: ₹" . ($row['payment_amount'] ?? 'N/A') . "\n";
    echo "  Created: {$row['created_at']}\n";
    echo "---\n";
}

echo "\n=== CLEANUP OPTIONS ===\n\n";
echo "1. Cancel and refund all unassigned paid consultations\n";
echo "   - Updates status to 'cancelled'\n";
echo "   - Initiates refund for paid consultations\n";
echo "   - Notifies affected patients\n\n";

echo "2. Assign to first available doctor (not recommended)\n";
echo "   - Randomly assigns to an available doctor\n";
echo "   - May not match patient's original intent\n\n";

echo "3. Exit without changes\n\n";

echo "⚠️  AUTOMATIC CLEANUP: Proceeding with Option 1 (Cancel & Refund)\n";
echo "This is the safest option as patients can re-book with correct doctor selection.\n\n";

// Option 1: Cancel and refund
$cancelled = 0;
$refunded = 0;

foreach ($consultations as $consult) {
    $consultation_id = $consult['id'];
    $patient_id = $consult['patient_id'];
    $transaction_id = $consult['transaction_id'];
    
    // Update consultation status
    $conn->query("
        UPDATE consultations 
        SET status = 'cancelled', 
            updated_at = NOW() 
        WHERE id = $consultation_id
    ");
    $cancelled++;
    
    // If paid, initiate refund
    if ($consult['payment_status'] == 'paid' && $transaction_id) {
        $refund_amount = floatval($consult['payment_amount']);
        
        // Update payment transaction
        $conn->query("
            UPDATE payment_transactions 
            SET status = 'refunded',
                refund_amount = $refund_amount,
                refund_status = 'initiated',
                refund_initiated_at = NOW(),
                refund_reason = 'Consultation cancelled - no doctor assignment'
            WHERE id = $transaction_id
        ");
        
        // Log refund
        $stmt = $conn->prepare("
            INSERT INTO consultation_refunds 
            (consultation_id, patient_id, refund_amount, refund_reason, refund_status)
            VALUES (?, ?, ?, 'No doctor assigned - system cleanup', 'initiated')
        ");
        $stmt->bind_param("iid", $consultation_id, $patient_id, $refund_amount);
        $stmt->execute();
        
        $refunded++;
        
        // Notify patient
        require_once 'notification_service.php';
        $notifService = getNotificationService();
        $notifService->send(
            $patient_id,
            'all',
            'Consultation Cancelled - Refund Initiated',
            "Your consultation request has been cancelled because no doctor was assigned. A full refund of ₹{$refund_amount} has been initiated and will be processed within 5-7 business days. Please re-book your consultation with a specific doctor.",
            ['notification_type' => 'consultation_cancelled', 'related_id' => $consultation_id]
        );
    }
    
    echo "✅ Processed consultation #{$consultation_id}\n";
}

echo "\n=== CLEANUP COMPLETE ===\n";
echo "Consultations cancelled: $cancelled\n";
echo "Refunds initiated: $refunded\n";
echo "\n";
echo "Next steps:\n";
echo "1. Patients will receive refund notifications\n";
echo "2. Refunds will be processed by payment gateway\n";
echo "3. Patients should re-book with proper doctor selection\n";