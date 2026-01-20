<?php
/**
 * Pharmacy API
 * Pharmacy management and prescription fulfillment
 */

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'db.php';
require_once 'notification_service.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please login first']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? '';

try {
    switch ($action) {
        
        // ==================================================
        // Get pending prescriptions (for pharmacy)
        // ==================================================
        case 'get_pending_prescriptions':
            if ($userRole !== 'pharmacy') {
                throw new Exception('Only pharmacies can access this');
            }
            
            // Get prescriptions routed to this pharmacy
            $stmt = $conn->prepare("
                SELECT p.*, 
                       c.symptoms, c.diagnosis as consultation_diagnosis,
                       pat.full_name as patient_name, pat.email as patient_email,
                       doc.full_name as doctor_name,
                       dp.specialization
                FROM prescriptions_v2 p
                JOIN consultations c ON p.consultation_id = c.id
                JOIN users pat ON p.patient_id = pat.id
                JOIN users doc ON p.doctor_id = doc.id
                LEFT JOIN doctor_profiles dp ON doc.id = dp.user_id
                WHERE p.pharmacy_id = ? AND p.status = 'sent_to_pharmacy'
                ORDER BY p.sent_at DESC
            ");
            
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $prescriptions = [];
            while ($row = $result->fetch_assoc()) {
                // Get prescription items
                $items = $conn->query("
                    SELECT * FROM prescription_items_v2
                    WHERE prescription_id = {$row['id']}
                ")->fetch_all(MYSQLI_ASSOC);
                
                $row['items'] = $items;
                $prescriptions[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'prescriptions' => $prescriptions
            ]);
            break;
        
        // ==================================================
        // Accept prescription and create order
        // ==================================================
        case 'accept_prescription':
            if ($userRole !== 'pharmacy') {
                throw new Exception('Only pharmacies can accept prescriptions');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            $prescriptionId = $input['prescription_id'] ?? 0;
            $totalAmount = floatval($input['total_amount'] ?? 0);
            $deliveryAvailable = $input['delivery_available'] ?? false;
            
            if (!$prescriptionId || $totalAmount <= 0) {
                throw new Exception('Invalid input');
            }
            
            // Get prescription details
            $stmt = $conn->prepare("
                SELECT * FROM prescriptions_v2
                WHERE id = ? AND pharmacy_id = ? AND status = 'sent_to_pharmacy'
            ");
            $stmt->bind_param("ii", $prescriptionId, $userId);
            $stmt->execute();
            $prescription = $stmt->get_result()->fetch_assoc();
            
            if (!$prescription) {
                throw new Exception('Prescription not found');
            }
            
            // Generate order number
            $orderNumber = 'ORD' . time() . rand(100, 999);
            
            // Create prescription order
            $fulfillmentType = $deliveryAvailable ? 'delivery' : 'pickup';
            
            $stmt = $conn->prepare("
                INSERT INTO prescription_orders (
                    order_number, prescription_id, pharmacy_id, patient_id,
                    total_amount, fulfillment_type, order_status
                ) VALUES (?, ?, ?, ?, ?, ?, 'accepted')
            ");
            
            $stmt->bind_param(
                "siiids",
                $orderNumber, $prescriptionId, $userId,
                $prescription['patient_id'], $totalAmount, $fulfillmentType
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to create order');
            }
            
            $orderId = $stmt->insert_id;
            
            // Update prescription status
            $conn->query("
                UPDATE prescriptions_v2
                SET status = 'filled', filled_at = NOW()
                WHERE id = $prescriptionId
            ");
            
            // Notify patient
            $notifService = getNotificationService();
            $notifService->send($prescription['patient_id'], 'all',
                'Prescription Accepted',
                "Your prescription has been accepted by the pharmacy. Order #: $orderNumber. Total: ₹$totalAmount",
                ['notification_type' => 'prescription_accepted', 'related_id' => $orderId]
            );
            
            echo json_encode([
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'message' => 'Prescription accepted and order created'
            ]);
            break;
        
        // ==================================================
        // Update order status
        // ==================================================
        case 'update_order_status':
            if ($userRole !== 'pharmacy') {
                throw new Exception('Only pharmacies can update orders');
            }
            
            $orderId = $_POST['order_id'] ?? 0;
            $status = $_POST['status'] ?? '';
            $notes = $_POST['notes'] ?? '';
            
            $validStatuses = ['preparing', 'ready', 'out_for_delivery', 'delivered', 'completed'];
            if (!in_array($status, $validStatuses)) {
                throw new Exception('Invalid status');
            }
            
            // Verify order belongs to pharmacy
            $stmt = $conn->prepare("
                SELECT patient_id FROM prescription_orders
                WHERE id = ? AND pharmacy_id = ?
            ");
            $stmt->bind_param("ii", $orderId, $userId);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();
            
            if (!$order) {
                throw new Exception('Order not found');
            }
            
            // Update order status
            $stmt = $conn->prepare("
                UPDATE prescription_orders
                SET order_status = ?, notes = CONCAT(COALESCE(notes, ''), ?)
                WHERE id = ?
            ");
            
            $noteEntry = "\n[" . date('Y-m-d H:i:s') . "] Status: $status - $notes";
            $stmt->bind_param("ssi", $status, $noteEntry, $orderId);
            $stmt->execute();
            
            // Update specific timestamps
            if ($status === 'ready') {
                $conn->query("UPDATE prescription_orders SET ready_at = NOW() WHERE id = $orderId");
            } elseif ($status === 'delivered') {
                $conn->query("UPDATE prescription_orders SET delivered_at = NOW() WHERE id = $orderId");
            }
            
            // Notify patient
            $notifService = getNotificationService();
            $messages = [
                'preparing' => 'Your order is being prepared',
                'ready' => 'Your order is ready for pickup/delivery',
                'out_for_delivery' => 'Your order is out for delivery',
                'delivered' => 'Your order has been delivered',
                'completed' => 'Your order is completed'
            ];
            
            $notifService->send($order['patient_id'], 'all',
                'Order Status Update',
                $messages[$status],
                ['notification_type' => 'order_status', 'related_id' => $orderId]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Order status updated'
            ]);
            break;
        
        // ==================================================
        // Get pharmacy orders
        // ==================================================
        case 'get_orders':
            if ($userRole !== 'pharmacy') {
                throw new Exception('Only pharmacies can access this');
            }
            
            $status = $_GET['status'] ?? 'all';
            
            $query = "
                SELECT po.*, 
                       u.full_name as patient_name,
                       u.email as patient_email,
                       p.diagnosis
                FROM prescription_orders po
                JOIN users u ON po.patient_id = u.id
                LEFT JOIN prescriptions_v2 p ON po.prescription_id = p.id
                WHERE po.pharmacy_id = ?
            ";
            
            if ($status !== 'all') {
                $query .= " AND po.order_status = '$status'";
            }
            
            $query .= " ORDER BY po.created_at DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $orders = [];
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'orders' => $orders
            ]);
            break;
        
        // ==================================================
        // Get pharmacy earnings
        // ==================================================
        case 'get_earnings':
            if ($userRole !== 'pharmacy') {
                throw new Exception('Only pharmacies can access this');
            }
            
            $period = $_GET['period'] ?? 'month'; // 'month', 'week', 'all'
            
            $dateFilter = '';
            if ($period === 'month') {
                $dateFilter = "AND DATE_FORMAT(pe.created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')";
            } elseif ($period === 'week') {
                $dateFilter = "AND pe.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            }
            
            $query = "
                SELECT 
                    SUM(gross_amount) as total_gross,
                    SUM(platform_commission_amount) as total_commission,
                    SUM(net_amount) as total_net,
                    COUNT(*) as order_count
                FROM pharmacy_earnings pe
                WHERE pe.pharmacy_id = ? $dateFilter
            ";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $earnings = $stmt->get_result()->fetch_assoc();
            
            echo json_encode([
                'success' => true,
                'earnings' => $earnings
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
