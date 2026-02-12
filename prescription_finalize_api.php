<?php
/**
 * Prescription Finalization API
 * Handles finalizing prescriptions and auto-sending to Central Pharmacy
 */

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'db.php';

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Doctor access required.']);
    exit;
}

$action = $_POST['action'] ?? '';
$doctorId = $_SESSION['user_id'];

// Get Central Pharmacy ID from database
$centralPharmacyResult = $conn->query("
    SELECT id FROM users 
    WHERE email = 'central.pharmacy@medconnect.com' 
    AND role = 'pharmacy' 
    LIMIT 1
");

if ($centralPharmacyResult->num_rows === 0) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Central Pharmacy not configured']);
    exit;
}

$CENTRAL_PHARMACY_ID = $centralPharmacyResult->fetch_assoc()['id'];

try {
    switch ($action) {
        
        // ==================================================
        // Finalize Prescription
        // ==================================================
        case 'finalize_prescription':
            $prescriptionId = intval($_POST['prescription_id'] ?? 0);
            
            if (!$prescriptionId) {
                throw new Exception('Prescription ID is required');
            }
            
            // Verify prescription belongs to this doctor and is in draft status
            $stmt = $conn->prepare("
                SELECT p.*, c.patient_id
                FROM prescriptions_v2 p
                JOIN consultations c ON p.consultation_id = c.id
                WHERE p.id = ? AND p.doctor_id = ? AND p.status = 'draft'
            ");
            $stmt->bind_param("ii", $prescriptionId, $doctorId);
            $stmt->execute();
            $prescription = $stmt->get_result()->fetch_assoc();
            
            if (!$prescription) {
                throw new Exception('Prescription not found or already finalized');
            }
            
            // Verify prescription has items
            $itemsCheck = $conn->query("
                SELECT COUNT(*) as count 
                FROM prescription_items_v2 
                WHERE prescription_id = $prescriptionId
            ");
            $itemCount = $itemsCheck->fetch_assoc()['count'];
            
            if ($itemCount === 0) {
                throw new Exception('Cannot finalize prescription without medicines');
            }
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Step 1: Update to FINALIZED
                $stmt = $conn->prepare("
                    UPDATE prescriptions_v2 
                    SET status = 'finalized',
                        finalized_at = NOW()
                    WHERE id = ?
                ");
                $stmt->bind_param("i", $prescriptionId);
                $stmt->execute();
                
                // Step 2: Auto-assign to Central Pharmacy and update to SENT_TO_PHARMACY
                $stmt = $conn->prepare("
                    UPDATE prescriptions_v2 
                    SET pharmacy_id = ?,
                        status = 'sent_to_pharmacy',
                        sent_to_pharmacy_at = NOW()
                    WHERE id = ?
                ");
                $stmt->bind_param("ii", $CENTRAL_PHARMACY_ID, $prescriptionId);
                $stmt->execute();
                
                // Step 3: Generate prescription number if not exists
                $prescriptionNumber = "RX-" . date('Y') . "-" . str_pad($prescriptionId, 6, '0', STR_PAD_LEFT);
                $stmt = $conn->prepare("
                    UPDATE prescriptions_v2 
                    SET prescription_number = ?
                    WHERE id = ? AND prescription_number IS NULL
                ");
                $stmt->bind_param("si", $prescriptionNumber, $prescriptionId);
                $stmt->execute();
                
                // Step 4: Create prescription order
                $orderNumber = "ORD-" . date('Y') . "-" . str_pad($prescriptionId, 6, '0', STR_PAD_LEFT);
                
                // Calculate total amount (simplified - sum of items * estimated price)
                $totalAmount = 0.00; // In production, calculate from pharmacy inventory
                
                $stmt = $conn->prepare("
                    INSERT INTO prescription_orders (
                        order_number,
                        prescription_id,
                        pharmacy_id,
                        patient_id,
                        order_status,
                        total_amount,
                        created_at
                    ) VALUES (?, ?, ?, ?, 'pending', ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                        order_status = 'pending',
                        updated_at = NOW()
                ");
                $orderStatus = 'pending';
                $stmt->bind_param("siiiid", 
                    $orderNumber, 
                    $prescriptionId, 
                    $CENTRAL_PHARMACY_ID, 
                    $prescription['patient_id'],
                    $totalAmount
                );
                $stmt->execute();
                
                // Commit transaction
                $conn->commit();
                
                // Get updated prescription details
                $result = $conn->query("
                    SELECT p.*, 
                           u.full_name as pharmacy_name
                    FROM prescriptions_v2 p
                    LEFT JOIN users u ON p.pharmacy_id = u.id
                    WHERE p.id = $prescriptionId
                ");
                $updatedPrescription = $result->fetch_assoc();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Prescription finalized and sent to pharmacy',
                    'prescription' => [
                        'id' => $updatedPrescription['id'],
                        'prescription_number' => $updatedPrescription['prescription_number'],
                        'status' => $updatedPrescription['status'],
                        'pharmacy_name' => $updatedPrescription['pharmacy_name'],
                        'finalized_at' => $updatedPrescription['finalized_at'],
                        'sent_to_pharmacy_at' => $updatedPrescription['sent_to_pharmacy_at']
                    ]
                ]);
                
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;
        
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
