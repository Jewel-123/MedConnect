<?php
/**
 * Patient Prescription API
 * Handles patient-side prescription actions
 */

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'db.php';

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Patient access required.']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$patientId = $_SESSION['user_id'];

try {
    switch ($action) {
        
        // ==================================================
        // Send Prescription to Pharmacy
        // ==================================================
        case 'send_to_pharmacy':
            $prescriptionId = intval($_POST['prescription_id'] ?? 0);
            
            if (!$prescriptionId) {
                throw new Exception('Prescription ID is required');
            }
            
            // Verify prescription belongs to patient and is finalized
            $stmt = $conn->prepare("
                SELECT id, status, pharmacy_id 
                FROM prescriptions_v2 
                WHERE id = ? AND patient_id = ?
            ");
            $stmt->bind_param("ii", $prescriptionId, $patientId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Prescription not found or does not belong to you');
            }
            
            $prescription = $result->fetch_assoc();
            
            if ($prescription['status'] !== 'finalized') {
                throw new Exception('Prescription must be finalized before sending to pharmacy');
            }
            
            // Get central pharmacy ID
            $centralPharmacy = $conn->query("
                SELECT id FROM users 
                WHERE email = 'central.pharmacy@medconnect.com' 
                LIMIT 1
            ")->fetch_assoc();
            
            if (!$centralPharmacy) {
                throw new Exception('Central pharmacy not found. Please contact support.');
            }
            
            $pharmacyId = $centralPharmacy['id'];
            
            // Update prescription status
            $stmt = $conn->prepare("
                UPDATE prescriptions_v2 
                SET status = 'sent_to_pharmacy',
                    pharmacy_id = ?,
                    sent_to_pharmacy_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("ii", $pharmacyId, $prescriptionId);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to send prescription to pharmacy');
            }
            
            // Create prescription order record
            $orderNumber = 'ORD' . time() . rand(1000, 9999);
            
            $stmt = $conn->prepare("
                INSERT INTO prescription_orders (
                    order_number, prescription_id, patient_id, pharmacy_id,
                    order_status, payment_status, created_at
                ) VALUES (?, ?, ?, ?, 'pending', 'pending', NOW())
            ");
            $stmt->bind_param("siii", $orderNumber, $prescriptionId, $patientId, $pharmacyId);
            
            if (!$stmt->execute()) {
                // Rollback prescription status if order creation fails
                $conn->query("UPDATE prescriptions_v2 SET status = 'finalized', pharmacy_id = NULL WHERE id = $prescriptionId");
                throw new Exception('Failed to create prescription order');
            }
            
            // Get updated prescription
            $updated = $conn->query("
                SELECT p.*, u.full_name as pharmacy_name
                FROM prescriptions_v2 p
                LEFT JOIN users u ON p.pharmacy_id = u.id
                WHERE p.id = $prescriptionId
            ")->fetch_assoc();
            
            echo json_encode([
                'success' => true,
                'message' => 'Order sent to pharmacy successfully! The pharmacy will review and prepare your medicines.',
                'prescription' => [
                    'id' => $updated['id'],
                    'status' => $updated['status'],
                    'pharmacy_name' => $updated['pharmacy_name'],
                    'sent_at' => $updated['sent_to_pharmacy_at'],
                    'order_number' => $orderNumber
                ]
            ]);
            break;
        
        // ==================================================
        // Get Prescription Payment Details
        // ==================================================
        case 'get_payment_details':
            $prescriptionId = intval($_GET['prescription_id'] ?? 0);
            
            if (!$prescriptionId) {
                throw new Exception('Prescription ID is required');
            }
            
            // Get prescription order details
            $stmt = $conn->prepare("
                SELECT po.*, p.prescription_number, p.diagnosis,
                       u.full_name as pharmacy_name,
                       pp.pharmacy_name as pharmacy_business_name
                FROM prescription_orders po
                JOIN prescriptions_v2 p ON po.prescription_id = p.id
                JOIN users u ON po.pharmacy_id = u.id
                LEFT JOIN pharmacy_profiles pp ON u.id = pp.user_id
                WHERE po.prescription_id = ? AND po.patient_id = ?
            ");
            $stmt->bind_param("ii", $prescriptionId, $patientId);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();
            
            if (!$order) {
                throw new Exception('Prescription order not found');
            }
            
            // Get prescription items with prices
            $items = $conn->query("
                SELECT pi.*, inv.unit_price, inv.stock_quantity
                FROM prescription_items_v2 pi
                LEFT JOIN pharmacy_inventory inv ON 
                    inv.medicine_name = pi.medicine_name AND 
                    inv.pharmacy_id = {$order['pharmacy_id']}
                WHERE pi.prescription_id = $prescriptionId
            ")->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode([
                'success' => true,
                'order' => $order,
                'items' => $items
            ]);
            break;
        
        // ==================================================
        // Check if Review Submitted
        // ==================================================
        case 'check_review_status':
            $prescriptionId = intval($_GET['prescription_id'] ?? 0);
            
            if (!$prescriptionId) {
                throw new Exception('Prescription ID is required');
            }
            
            // Check if review exists
            $stmt = $conn->prepare("
                SELECT id FROM prescription_reviews 
                WHERE prescription_id = ? AND patient_id = ?
            ");
            $stmt->bind_param("ii", $prescriptionId, $patientId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            echo json_encode([
                'success' => true,
                'review_submitted' => $result->num_rows > 0
            ]);
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
?>
