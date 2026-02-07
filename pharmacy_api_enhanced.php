<?php
/**
 * Enhanced Pharmacy API
 * Comprehensive pharmacy management with inventory, notifications, and analytics
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

// Helper function to create pharmacy notification
function createPharmacyNotification($conn, $pharmacyId, $type, $title, $message, $relatedId = null, $relatedType = null, $priority = 'medium') {
    $stmt = $conn->prepare("
        INSERT INTO pharmacy_notifications (pharmacy_id, notification_type, title, message, related_id, related_type, priority)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssiis", $pharmacyId, $type, $title, $message, $relatedId, $relatedType, $priority);
    return $stmt->execute();
}

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
                       c.symptoms, c.diagnosis as consultation_diagnosis, c.urgency_level,
                       pat.full_name as patient_name, pat.email as patient_email, pat.phone as patient_phone,
                       doc.full_name as doctor_name,
                       dp.specialization, dp.license_number as doctor_license
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
                'prescriptions' => $prescriptions,
                'count' => count($prescriptions)
            ]);
            break;
        
        // ==================================================
        // Check inventory availability
        // ==================================================
        case 'check_inventory':
            if ($userRole !== 'pharmacy') {
                throw new Exception('Only pharmacies can access inventory');
            }
            
            $medicineName = $_GET['medicine_name'] ?? '';
            
            if (empty($medicineName)) {
                throw new Exception('Medicine name required');
            }
            
            $stmt = $conn->prepare("
                SELECT * FROM pharmacy_inventory
                WHERE pharmacy_id = ? AND medicine_name LIKE ?
                ORDER BY expiry_date ASC
            ");
            
            $searchTerm = "%$medicineName%";
            $stmt->bind_param("is", $userId, $searchTerm);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $inventory = [];
            while ($row = $result->fetch_assoc()) {
                $inventory[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'inventory' => $inventory,
                'available' => count($inventory) > 0 && $inventory[0]['stock_quantity'] > 0
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
                    total_amount, fulfillment_type, order_status, accepted_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'accepted', NOW())
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
            
            // Create pharmacy earnings record
            $platformCommission = 5.00; // 5% commission
            $commissionAmount = ($totalAmount * $platformCommission) / 100;
            $netAmount = $totalAmount - $commissionAmount;
            
            $stmt = $conn->prepare("
                INSERT INTO pharmacy_earnings (pharmacy_id, prescription_order_id, gross_amount, platform_commission_percent, platform_commission_amount, net_amount)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iidddd", $userId, $orderId, $totalAmount, $platformCommission, $commissionAmount, $netAmount);
            $stmt->execute();
            
            // Notify patient
            $notifService = getNotificationService();
            $notifService->send($prescription['patient_id'], 'all',
                'Prescription Accepted',
                "Your prescription has been accepted by the pharmacy. Order #: $orderNumber. Total: ₹$totalAmount",
                ['notification_type' => 'prescription_accepted', 'related_id' => $orderId]
            );
            
            // Create pharmacy notification
            createPharmacyNotification($conn, $userId, 'order_update', 'Order Created', 
                "New order #$orderNumber created for ₹$totalAmount", $orderId, 'order', 'medium');
            
            echo json_encode([
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'message' => 'Prescription accepted and order created'
            ]);
            break;
        
        // ==================================================
        // Reject prescription
        // ==================================================
        case 'reject_prescription':
            if ($userRole !== 'pharmacy') {
                throw new Exception('Only pharmacies can reject prescriptions');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            $prescriptionId = $input['prescription_id'] ?? 0;
            $reason = $input['reason'] ?? 'Not specified';
            
            if (!$prescriptionId) {
                throw new Exception('Prescription ID required');
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
            
            // Update prescription status
            $stmt = $conn->prepare("
                UPDATE prescriptions_v2
                SET status = 'cancelled', notes_for_patient = CONCAT(COALESCE(notes_for_patient, ''), '\n\nRejected by pharmacy: ', ?)
                WHERE id = ?
            ");
            $stmt->bind_param("si", $reason, $prescriptionId);
            $stmt->execute();
            
            // Notify patient and doctor
            $notifService = getNotificationService();
            $notifService->send($prescription['patient_id'], 'all',
                'Prescription Rejected',
                "Your prescription has been rejected by the pharmacy. Reason: $reason. Please contact your doctor for an alternative.",
                ['notification_type' => 'prescription_rejected', 'related_id' => $prescriptionId]
            );
            
            $notifService->send($prescription['doctor_id'], 'in_app',
                'Prescription Rejected',
                "Your prescription for patient has been rejected by pharmacy. Reason: $reason",
                ['notification_type' => 'prescription_rejected', 'related_id' => $prescriptionId]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Prescription rejected successfully'
            ]);
            break;
        
        // ==================================================
        // Update order status
        // ==================================================
        case 'update_order_status':
            if ($userRole !== 'pharmacy') {
                throw new Exception('Only pharmacies can update orders');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            $orderId = $input['order_id'] ?? 0;
            $status = $input['status'] ?? '';
            $notes = $input['notes'] ?? '';
            
            $validStatuses = ['preparing', 'ready', 'out_for_delivery', 'delivered', 'completed'];
            if (!in_array($status, $validStatuses)) {
                throw new Exception('Invalid status');
            }
            
            // Verify order belongs to pharmacy
            $stmt = $conn->prepare("
                SELECT patient_id, order_number FROM prescription_orders
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
            if ($status === 'preparing') {
                $conn->query("UPDATE prescription_orders SET packaging_completed_at = NULL WHERE id = $orderId");
            } elseif ($status === 'ready') {
                $conn->query("UPDATE prescription_orders SET ready_at = NOW(), packaging_completed_at = NOW() WHERE id = $orderId");
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
                $messages[$status] . " (Order #{$order['order_number']})",
                ['notification_type' => 'order_status', 'related_id' => $orderId]
            );
            
            // Create pharmacy notification
            createPharmacyNotification($conn, $userId, 'order_update', 'Order Status Updated', 
                "Order #{$order['order_number']} status changed to $status", $orderId, 'order', 'low');
            
            echo json_encode([
                'success' => true,
                'message' => 'Order status updated'
            ]);
            break;
        
        // ==================================================
        // Confirm payment
        // ==================================================
        case 'confirm_payment':
            if ($userRole !== 'pharmacy') {
                throw new Exception('Only pharmacies can confirm payments');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            $orderId = $input['order_id'] ?? 0;
            $paymentMethod = $input['payment_method'] ?? 'cash';
            
            if (!$orderId) {
                throw new Exception('Order ID required');
            }
            
            // Verify order belongs to pharmacy
            $stmt = $conn->prepare("
                SELECT patient_id, order_number, total_amount FROM prescription_orders
                WHERE id = ? AND pharmacy_id = ?
            ");
            $stmt->bind_param("ii", $orderId, $userId);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();
            
            if (!$order) {
                throw new Exception('Order not found');
            }
            
            // Update payment status
            $stmt = $conn->prepare("
                UPDATE prescription_orders
                SET payment_status = 'paid', payment_confirmed_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            
            // Notify patient
            $notifService = getNotificationService();
            $notifService->send($order['patient_id'], 'all',
                'Payment Confirmed',
                "Payment of ₹{$order['total_amount']} confirmed for order #{$order['order_number']}",
                ['notification_type' => 'payment_confirmed', 'related_id' => $orderId]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Payment confirmed successfully'
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
                       u.phone as patient_phone,
                       p.diagnosis,
                       p.prescription_number
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
                'orders' => $orders,
                'count' => count($orders)
            ]);
            break;
        
        // ==================================================
        // Get pharmacy earnings
        // ==================================================
        case 'get_earnings':
            if ($userRole !== 'pharmacy') {
                throw new Exception('Only pharmacies can access this');
            }
            
            $period = $_GET['period'] ?? 'month'; // 'month', 'week', 'today', 'all'
            
            $dateFilter = '';
            if ($period === 'month') {
                $dateFilter = "AND DATE_FORMAT(pe.created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')";
            } elseif ($period === 'week') {
                $dateFilter = "AND pe.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            } elseif ($period === 'today') {
                $dateFilter = "AND DATE(pe.created_at) = CURDATE()";
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
        
        // ==================================================
        // Get dashboard statistics
        // ==================================================
        case 'get_dashboard_stats':
            if ($userRole !== 'pharmacy') {
                throw new Exception('Only pharmacies can access this');
            }
            
            // Pending prescriptions
            $pendingCount = $conn->query("
                SELECT COUNT(*) as count FROM prescriptions_v2
                WHERE pharmacy_id = $userId AND status = 'sent_to_pharmacy'
            ")->fetch_assoc()['count'];
            
            // Active orders
            $activeCount = $conn->query("
                SELECT COUNT(*) as count FROM prescription_orders
                WHERE pharmacy_id = $userId AND order_status NOT IN ('completed', 'cancelled', 'delivered')
            ")->fetch_assoc()['count'];
            
            // Today's earnings
            $todayEarnings = $conn->query("
                SELECT COALESCE(SUM(net_amount), 0) as total FROM pharmacy_earnings
                WHERE pharmacy_id = $userId AND DATE(created_at) = CURDATE()
            ")->fetch_assoc()['total'];
            
            // Monthly earnings
            $monthEarnings = $conn->query("
                SELECT COALESCE(SUM(net_amount), 0) as total FROM pharmacy_earnings
                WHERE pharmacy_id = $userId AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
            ")->fetch_assoc()['total'];
            
            // Total orders
            $totalOrders = $conn->query("
                SELECT COUNT(*) as count FROM prescription_orders
                WHERE pharmacy_id = $userId
            ")->fetch_assoc()['count'];
            
            // Fulfillment rate
            $completedOrders = $conn->query("
                SELECT COUNT(*) as count FROM prescription_orders
                WHERE pharmacy_id = $userId AND order_status IN ('completed', 'delivered')
            ")->fetch_assoc()['count'];
            
            $fulfillmentRate = $totalOrders > 0 ? ($completedOrders / $totalOrders) * 100 : 0;
            
            // Low stock alerts
            $lowStockCount = $conn->query("
                SELECT COUNT(*) as count FROM pharmacy_inventory
                WHERE pharmacy_id = $userId AND stock_quantity <= low_stock_threshold AND is_available = TRUE
            ")->fetch_assoc()['count'];
            
            echo json_encode([
                'success' => true,
                'stats' => [
                    'pending_prescriptions' => $pendingCount,
                    'active_orders' => $activeCount,
                    'today_earnings' => floatval($todayEarnings),
                    'month_earnings' => floatval($monthEarnings),
                    'total_orders' => $totalOrders,
                    'fulfillment_rate' => round($fulfillmentRate, 2),
                    'low_stock_alerts' => $lowStockCount
                ]
            ]);
            break;
        
        // ==================================================
        // Get pharmacy notifications
        // ==================================================
        case 'get_notifications':
            if ($userRole !== 'pharmacy') {
                throw new Exception('Only pharmacies can access this');
            }
            
            $limit = intval($_GET['limit'] ?? 50);
            $unreadOnly = ($_GET['unread_only'] ?? 'false') === 'true';
            
            $query = "
                SELECT * FROM pharmacy_notifications
                WHERE pharmacy_id = ?
            ";
            
            if ($unreadOnly) {
                $query .= " AND is_read = FALSE";
            }
            
            $query .= " ORDER BY created_at DESC LIMIT ?";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $userId, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $notifications = [];
            while ($row = $result->fetch_assoc()) {
                $notifications[] = $row;
            }
            
            // Get unread count
            $unreadCount = $conn->query("
                SELECT COUNT(*) as count FROM pharmacy_notifications
                WHERE pharmacy_id = $userId AND is_read = FALSE
            ")->fetch_assoc()['count'];
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]);
            break;
        
        // ==================================================
        // Mark notification as read
        // ==================================================
        case 'mark_notification_read':
            if ($userRole !== 'pharmacy') {
                throw new Exception('Only pharmacies can access this');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $notificationId = $input['notification_id'] ?? 0;
            $markAll = $input['mark_all'] ?? false;
            
            if ($markAll) {
                $stmt = $conn->prepare("
                    UPDATE pharmacy_notifications
                    SET is_read = TRUE, read_at = NOW()
                    WHERE pharmacy_id = ? AND is_read = FALSE
                ");
                $stmt->bind_param("i", $userId);
            } else {
                $stmt = $conn->prepare("
                    UPDATE pharmacy_notifications
                    SET is_read = TRUE, read_at = NOW()
                    WHERE id = ? AND pharmacy_id = ?
                ");
                $stmt->bind_param("ii", $notificationId, $userId);
            }
            
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Notification(s) marked as read'
            ]);
            break;
        
        // ==================================================
        // Get prescription history
        // ==================================================
        case 'get_prescription_history':
            if ($userRole !== 'pharmacy') {
                throw new Exception('Only pharmacies can access this');
            }
            
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $status = $_GET['status'] ?? 'all';
            $limit = intval($_GET['limit'] ?? 100);
            
            $query = "
                SELECT p.*, 
                       c.symptoms,
                       pat.full_name as patient_name,
                       doc.full_name as doctor_name,
                       po.order_number, po.order_status, po.total_amount
                FROM prescriptions_v2 p
                JOIN consultations c ON p.consultation_id = c.id
                JOIN users pat ON p.patient_id = pat.id
                JOIN users doc ON p.doctor_id = doc.id
                LEFT JOIN prescription_orders po ON p.id = po.prescription_id
                WHERE p.pharmacy_id = ?
            ";
            
            if ($status !== 'all') {
                $query .= " AND p.status = '$status'";
            }
            
            if ($startDate) {
                $query .= " AND DATE(p.created_at) >= '$startDate'";
            }
            
            if ($endDate) {
                $query .= " AND DATE(p.created_at) <= '$endDate'";
            }
            
            $query .= " ORDER BY p.created_at DESC LIMIT ?";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $userId, $limit);
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
                'prescriptions' => $prescriptions,
                'count' => count($prescriptions)
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