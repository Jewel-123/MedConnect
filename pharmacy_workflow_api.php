<?php
/**
 * Pharmacy Workflow API
 * Handles prescription status updates for pharmacy dashboard
 */

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'db.php';

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacy') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Pharmacy access required.']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$pharmacyId = $_SESSION['user_id'];

// Allowed status transitions
$allowedTransitions = [
    'sent_to_pharmacy' => ['in_progress', 'cancelled'],
    'in_progress' => ['ready', 'cancelled'],
    'ready' => ['completed', 'cancelled']
];

try {
    switch ($action) {
        
        // ==================================================
        // Get Pharmacy Dashboard Data by Tab
        // ==================================================
        case 'get_dashboard':
            $tab = $_GET['tab'] ?? 'pending';
            
            switch ($tab) {
                case 'pending':
                    // Prescriptions with status SENT_TO_PHARMACY
                    $prescriptions = $conn->query("
                        SELECT p.*, 
                               c.symptoms,
                               pat.full_name as patient_name,
                               pat.phone as patient_phone,
                               pat.email as patient_email,
                               doc.full_name as doctor_name,
                               dp.specialization,
                               COUNT(pi.id) as medicine_count
                        FROM prescriptions_v2 p
                        JOIN consultations c ON p.consultation_id = c.id
                        JOIN users pat ON p.patient_id = pat.id
                        JOIN users doc ON p.doctor_id = doc.id
                        LEFT JOIN doctor_profiles dp ON doc.id = dp.user_id
                        LEFT JOIN prescription_items_v2 pi ON p.id = pi.prescription_id
                        WHERE p.pharmacy_id = $pharmacyId
                        AND p.status = 'sent_to_pharmacy'
                        GROUP BY p.id
                        ORDER BY p.sent_to_pharmacy_at ASC
                    ")->fetch_all(MYSQLI_ASSOC);
                    break;
                
                case 'active':
                    // Prescriptions with status IN_PROGRESS
                    $prescriptions = $conn->query("
                        SELECT p.*, 
                               po.order_number,
                               po.total_amount,
                               pat.full_name as patient_name,
                               pat.phone as patient_phone,
                               pat.email as patient_email,
                               doc.full_name as doctor_name
                        FROM prescriptions_v2 p
                        JOIN prescription_orders po ON p.id = po.prescription_id
                        JOIN users pat ON p.patient_id = pat.id
                        JOIN users doc ON p.doctor_id = doc.id
                        WHERE p.pharmacy_id = $pharmacyId
                        AND p.status = 'in_progress'
                        ORDER BY p.in_progress_at ASC
                    ")->fetch_all(MYSQLI_ASSOC);
                    break;
                
                case 'ready':
                    // Prescriptions with status READY
                    $prescriptions = $conn->query("
                        SELECT p.*, 
                               po.order_number,
                               po.total_amount,
                               po.fulfillment_type,
                               pat.full_name as patient_name,
                               pat.phone as patient_phone,
                               pat.email as patient_email
                        FROM prescriptions_v2 p
                        JOIN prescription_orders po ON p.id = po.prescription_id
                        JOIN users pat ON p.patient_id = pat.id
                        WHERE p.pharmacy_id = $pharmacyId
                        AND p.status = 'ready'
                        ORDER BY p.ready_at ASC
                    ")->fetch_all(MYSQLI_ASSOC);
                    break;
                
                case 'completed':
                    // Prescriptions with status COMPLETED
                    $prescriptions = $conn->query("
                        SELECT p.*, 
                               po.order_number,
                               po.total_amount,
                               pat.full_name as patient_name,
                               DATE(p.completed_at) as completion_date
                        FROM prescriptions_v2 p
                        JOIN prescription_orders po ON p.id = po.prescription_id
                        JOIN users pat ON p.patient_id = pat.id
                        WHERE p.pharmacy_id = $pharmacyId
                        AND p.status = 'completed'
                        ORDER BY p.completed_at DESC
                        LIMIT 50
                    ")->fetch_all(MYSQLI_ASSOC);
                    break;
                
                case 'analytics':
                    // Dashboard analytics
                    $analytics = [];
                    
                    // Month earnings
                    $monthEarnings = $conn->query("
                        SELECT COALESCE(SUM(total_amount), 0) as month_earnings
                        FROM prescription_orders
                        WHERE pharmacy_id = $pharmacyId
                        AND order_status = 'completed'
                        AND MONTH(completed_at) = MONTH(CURRENT_DATE)
                        AND YEAR(completed_at) = YEAR(CURRENT_DATE)
                    ")->fetch_assoc();
                    
                    // Fulfillment rate
                    $fulfillment = $conn->query("
                        SELECT 
                            COUNT(*) as total_prescriptions,
                            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                            ROUND((SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as fulfillment_rate
                        FROM prescriptions_v2
                        WHERE pharmacy_id = $pharmacyId
                        AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                    ")->fetch_assoc();
                    
                    // Average processing time
                    $avgTime = $conn->query("
                        SELECT 
                            AVG(TIMESTAMPDIFF(MINUTE, sent_to_pharmacy_at, ready_at)) as avg_prep_time_minutes
                        FROM prescriptions_v2
                        WHERE pharmacy_id = $pharmacyId
                        AND status IN ('ready', 'completed')
                        AND ready_at IS NOT NULL
                    ")->fetch_assoc();
                    
                    // Pending queue count
                    $pending = $conn->query("
                        SELECT COUNT(*) as pending_count
                        FROM prescriptions_v2
                        WHERE pharmacy_id = $pharmacyId
                        AND status = 'sent_to_pharmacy'
                    ")->fetch_assoc();
                    
                    echo json_encode([
                        'success' => true,
                        'analytics' => [
                            'month_earnings' => floatval($monthEarnings['month_earnings']),
                            'total_prescriptions' => intval($fulfillment['total_prescriptions']),
                            'completed' => intval($fulfillment['completed']),
                            'fulfillment_rate' => floatval($fulfillment['fulfillment_rate']),
                            'avg_prep_time_minutes' => round(floatval($avgTime['avg_prep_time_minutes'] ?? 0), 2),
                            'pending_count' => intval($pending['pending_count'])
                        ]
                    ]);
                    return;
                
                default:
                    throw new Exception('Invalid tab');
            }
            
            // Get items for each prescription
            foreach ($prescriptions as &$rx) {
                $rx['items'] = $conn->query("
                    SELECT * FROM prescription_items_v2
                    WHERE prescription_id = {$rx['id']}
                ")->fetch_all(MYSQLI_ASSOC);
            }
            
            echo json_encode([
                'success' => true,
                'tab' => $tab,
                'prescriptions' => $prescriptions,
                'count' => count($prescriptions)
            ]);
            break;
        
        // ==================================================
        // Update Prescription Status
        // ==================================================
        case 'update_status':
            $prescriptionId = intval($_POST['prescription_id'] ?? 0);
            $newStatus = $_POST['new_status'] ?? '';
            $cancellationReason = $_POST['cancellation_reason'] ?? null;
            
            if (!$prescriptionId || !$newStatus) {
                throw new Exception('Prescription ID and new status are required');
            }
            
            // Verify prescription belongs to this pharmacy
            $stmt = $conn->prepare("
                SELECT status 
                FROM prescriptions_v2 
                WHERE id = ? AND pharmacy_id = ?
            ");
            $stmt->bind_param("ii", $prescriptionId, $pharmacyId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Prescription not found or does not belong to this pharmacy');
            }
            
            $currentStatus = $result->fetch_assoc()['status'];
            
            // Validate transition
            if (!isset($allowedTransitions[$currentStatus]) || 
                !in_array($newStatus, $allowedTransitions[$currentStatus])) {
                throw new Exception("Invalid status transition from $currentStatus to $newStatus");
            }
            
            // If cancelling, require reason
            if ($newStatus === 'cancelled' && empty($cancellationReason)) {
                throw new Exception('Cancellation reason is required');
            }
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Update prescription status with appropriate timestamp
                $timestampField = '';
                switch ($newStatus) {
                    case 'in_progress':
                        $timestampField = 'in_progress_at';
                        break;
                    case 'ready':
                        $timestampField = 'ready_at';
                        break;
                    case 'completed':
                        $timestampField = 'completed_at';
                        break;
                    case 'cancelled':
                        $timestampField = 'cancelled_at';
                        break;
                }
                
                if ($newStatus === 'cancelled') {
                    $stmt = $conn->prepare("
                        UPDATE prescriptions_v2 
                        SET status = ?,
                            $timestampField = NOW(),
                            cancellation_reason = ?
                        WHERE id = ?
                    ");
                    $stmt->bind_param("ssi", $newStatus, $cancellationReason, $prescriptionId);
                } else {
                    $stmt = $conn->prepare("
                        UPDATE prescriptions_v2 
                        SET status = ?,
                            $timestampField = NOW()
                        WHERE id = ?
                    ");
                    $stmt->bind_param("si", $newStatus, $prescriptionId);
                }
                $stmt->execute();
                
                // Update prescription_orders table if exists
                $orderStatusMap = [
                    'in_progress' => 'in_progress',
                    'ready' => 'ready',
                    'completed' => 'completed',
                    'cancelled' => 'cancelled'
                ];
                
                if (isset($orderStatusMap[$newStatus])) {
                    $orderStatus = $orderStatusMap[$newStatus];
                    $conn->query("
                        UPDATE prescription_orders 
                        SET order_status = '$orderStatus',
                            updated_at = NOW()
                        WHERE prescription_id = $prescriptionId
                    ");
                }
                
                $conn->commit();
                
                // Get updated prescription
                $updated = $conn->query("
                    SELECT * FROM prescriptions_v2 WHERE id = $prescriptionId
                ")->fetch_assoc();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Prescription status updated successfully',
                    'prescription' => [
                        'id' => $updated['id'],
                        'status' => $updated['status'],
                        $timestampField => $updated[$timestampField]
                    ]
                ]);
                
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;
        
        // ==================================================
        // Accept Prescription and Create Order
        // ==================================================
        case 'accept_prescription':
            $prescriptionId = intval($_POST['prescription_id'] ?? 0);
            $totalAmount = floatval($_POST['total_amount'] ?? 0);
            $deliveryAvailable = filter_var($_POST['delivery_available'] ?? true, FILTER_VALIDATE_BOOLEAN);
            
            if (!$prescriptionId || $totalAmount <= 0) {
                throw new Exception('Prescription ID and total amount are required');
            }
            
            // Verify prescription belongs to this pharmacy and is sent_to_pharmacy
            $stmt = $conn->prepare("
                SELECT p.*, u.full_name as patient_name
                FROM prescriptions_v2 p
                JOIN users u ON p.patient_id = u.id
                WHERE p.id = ? AND p.pharmacy_id = ? AND p.status = 'sent_to_pharmacy'
            ");
            $stmt->bind_param("ii", $prescriptionId, $pharmacyId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Prescription not found or already processed');
            }
            
            $prescription = $result->fetch_assoc();
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Generate order number
                $orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad($prescriptionId, 6, '0', STR_PAD_LEFT);
                
                // Create prescription order
                $stmt = $conn->prepare("
                    INSERT INTO prescription_orders (
                        order_number, prescription_id, pharmacy_id, patient_id,
                        order_status, fulfillment_type, total_amount, 
                        payment_status, accepted_at, created_at
                    ) VALUES (?, ?, ?, ?, 'accepted', 'pickup', ?, 'pending', NOW(), NOW())
                ");
                $orderStatus = 'accepted';
                $stmt->bind_param("siiiid", 
                    $orderNumber, $prescriptionId, $pharmacyId, 
                    $prescription['patient_id'], $totalAmount
                );
                $stmt->execute();
                $orderId = $conn->insert_id;
                
                // Update prescription status to in_progress
                $stmt = $conn->prepare("
                    UPDATE prescriptions_v2 
                    SET status = 'in_progress',
                        in_progress_at = NOW()
                    WHERE id = ?
                ");
                $stmt->bind_param("i", $prescriptionId);
                $stmt->execute();
                
                $conn->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Prescription accepted successfully',
                    'order' => [
                        'id' => $orderId,
                        'order_number' => $orderNumber,
                        'total_amount' => $totalAmount,
                        'status' => 'accepted'
                    ]
                ]);
                
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;
        
        // ==================================================
        // Mark Order as Ready
        // ==================================================
        case 'mark_as_ready':
            $prescriptionId = intval($_POST['prescription_id'] ?? 0);
            
            if (!$prescriptionId) {
                throw new Exception('Prescription ID is required');
            }
            
            // Verify prescription belongs to this pharmacy and is in_progress
            $stmt = $conn->prepare("
                SELECT id FROM prescriptions_v2 
                WHERE id = ? AND pharmacy_id = ? AND status = 'in_progress'
            ");
            $stmt->bind_param("ii", $prescriptionId, $pharmacyId);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception('Prescription not found or not in progress');
            }
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Update prescription status to ready
                $stmt = $conn->prepare("
                    UPDATE prescriptions_v2 
                    SET status = 'ready',
                        ready_at = NOW()
                    WHERE id = ?
                ");
                $stmt->bind_param("i", $prescriptionId);
                $stmt->execute();
                
                // Update prescription_orders status
                $conn->query("
                    UPDATE prescription_orders 
                    SET order_status = 'ready',
                        ready_at = NOW(),
                        updated_at = NOW()
                    WHERE prescription_id = $prescriptionId
                ");
                
                $conn->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Order marked as ready successfully'
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
