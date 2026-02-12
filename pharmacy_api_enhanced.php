<?php
/**
 * Enhanced Pharmacy API
 * Comprehensive pharmacy management with inventory, notifications, and analytics
 */

// Start output buffering to catch any stray output
ob_start();

session_start();

// Clean the buffer and start fresh to ensure only JSON is output
ob_end_clean();
ob_start();

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'db.php';
require_once 'notification_service.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    ob_end_clean(); // Clean buffer before output
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
                       c.symptoms, c.urgency_level,
                       pat.full_name as patient_name, pat.email as patient_email, pat.phone as patient_phone,
                       doc.full_name as doctor_name,
                       dp.specialization, dp.license_number as doctor_license
                FROM prescriptions_v2 p
                LEFT JOIN consultations c ON p.consultation_id = c.id
                JOIN users pat ON p.patient_id = pat.id
                JOIN users doc ON p.doctor_id = doc.id
                LEFT JOIN doctor_profiles dp ON doc.id = dp.user_id
                WHERE p.pharmacy_id = ? 
                AND p.status IN ('Pending', 'Verified', 'Awaiting Payment', 'Paid', 'Dispensed')
                AND p.ordered_at IS NOT NULL
                ORDER BY p.ordered_at DESC
            ");
            
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $prescriptions = [];
            while ($row = $result->fetch_assoc()) {
                // Get prescription items with medicine details and pricing
                $items = $conn->query("
                    SELECT pi.*, 
                           m.id as medicine_id, m.name as medicine_name, 
                           m.price, m.stock, m.generic_name, m.category, m.unit
                    FROM prescription_items_v2 pi
                    LEFT JOIN medicines m ON pi.medicine_id = m.id OR pi.medicine_name = m.name
                    WHERE pi.prescription_id = {$row['id']}
                ")->fetch_all(MYSQLI_ASSOC);
                
                // Calculate total for display
                $total = 0;
                foreach ($items as &$item) {
                    $item['price'] = $item['price'] ?? 0;
                    $item['quantity'] = intval($item['quantity'] ?? 1);
                    $item['line_total'] = $item['price'] * $item['quantity'];
                    $total += $item['line_total'];
                }
                
                $row['items'] = $items;
                $row['calculated_total'] = $total;
                $prescriptions[] = $row;
            }
            
            ob_end_clean(); // Clean buffer before JSON output
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
        // ==================================================
        // Stage 1: Verify Prescription (Pending -> Verified)
        // ==================================================
        case 'verify_prescription':
            if ($userRole !== 'pharmacy') {
                throw new Exception('Unauthorized');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $prescriptionId = $input['prescription_id'] ?? 0;
            
            if (!$prescriptionId) {
                throw new Exception('Invalid prescription ID');
            }
            
            // Validate current status (Pending or sent_to_pharmacy)
            $stmt = $conn->prepare("
                SELECT status FROM prescriptions_v2
                WHERE id = ? AND pharmacy_id = ?
            ");
            $stmt->bind_param("ii", $prescriptionId, $userId);
            $stmt->execute();
            $presc = $stmt->get_result()->fetch_assoc();
            
            if (!$presc) throw new Exception('Prescription not found');
            
            $allowed = ['Pending', 'sent_to_pharmacy'];
            if (!in_array($presc['status'], $allowed)) {
                throw new Exception('Invalid transition. Current status: ' . $presc['status']);
            }
            
            // Transition to Verified
            $stmt = $conn->prepare("
                UPDATE prescriptions_v2
                SET status = 'Verified',
                    verified_at = NOW(),
                    pharmacist_id = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ii", $userId, $prescriptionId);
            
            if (!$stmt->execute()) throw new Exception('Failed to verify prescription');
            
            echo json_encode(['success' => true, 'message' => 'Prescription verified. Next: Generate Bill.']);
            break;

        // ==================================================
        // Stage 2: Generate Bill (Verified -> Awaiting Payment)
        // ==================================================
        case 'generate_bill':
            if ($userRole !== 'pharmacy') {
                throw new Exception('Unauthorized');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $prescriptionId = $input['prescription_id'] ?? 0;
            
            if (!$prescriptionId) throw new Exception('Invalid prescription ID');
            
            // Validate current status
            $stmt = $conn->prepare("
                SELECT status, patient_id FROM prescriptions_v2
                WHERE id = ? AND pharmacy_id = ?
            ");
            $stmt->bind_param("ii", $prescriptionId, $userId);
            $stmt->execute();
            $presc = $stmt->get_result()->fetch_assoc();
            
            if (!$presc) throw new Exception('Prescription not found');
            if ($presc['status'] !== 'Verified') {
                throw new Exception('Prescription must be Verified before generating bill. Current status: ' . $presc['status']);
            }
            
            // Calculate total from medicines table using medicine_id FK
            $items = $conn->query("
                SELECT pi.*, m.price, m.stock, m.name as med_name
                FROM prescription_items_v2 pi
                LEFT JOIN medicines m ON pi.medicine_id = m.id OR pi.medicine_name = m.name
                WHERE pi.prescription_id = $prescriptionId
            ")->fetch_all(MYSQLI_ASSOC);
            
            if (empty($items)) {
                throw new Exception('No prescription items found');
            }
            
            $totalAmount = 0;
            $missingPrices = [];
            
            foreach ($items as $item) {
                if (!$item['price']) {
                    $missingPrices[] = $item['medicine_name'] ?? 'Unknown';
                    continue;
                }
                $price = floatval($item['price']);
                $quantity = intval($item['quantity'] ?? 1);
                $totalAmount += ($price * $quantity);
            }
            
            if (!empty($missingPrices)) {
                throw new Exception('Price not found for medicines: ' . implode(', ', $missingPrices));
            }
            
            // Update prescription
            $stmt = $conn->prepare("
                UPDATE prescriptions_v2
                SET status = 'Awaiting Payment',
                    total_amount = ?,
                    bill_generated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("di", $totalAmount, $prescriptionId);
            
            if (!$stmt->execute()) throw new Exception('Failed to generate bill');
            
            // Update legacy order record if exists
            $stmt = $conn->prepare("UPDATE prescription_orders SET order_status = 'awaiting_payment', total_amount = ? WHERE prescription_id = ?");
            $stmt->bind_param("di", $totalAmount, $prescriptionId);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Bill generated: ₹' . number_format($totalAmount, 2), 'total' => $totalAmount]);
            break;

        // ==================================================
        // Dispense prescription (Only if Paid)
        // ==================================================
        // ==================================================
        // Stage 3: Dispense Prescription (Paid -> Dispensed)
        // ==================================================
        case 'dispense_prescription':
            if ($userRole !== 'pharmacy') {
                throw new Exception('Unauthorized');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $prescriptionId = $input['prescription_id'] ?? 0;
            
            if (!$prescriptionId) throw new Exception('Invalid prescription ID');
            
            // Check status
            $stmt = $conn->prepare("SELECT status FROM prescriptions_v2 WHERE id = ? AND pharmacy_id = ?");
            $stmt->bind_param("ii", $prescriptionId, $userId);
            $stmt->execute();
            $rx = $stmt->get_result()->fetch_assoc();
            
            if (!$rx) throw new Exception('Prescription not found');
            if ($rx['status'] !== 'Paid') {
                throw new Exception('Cannot dispense: Prescription not paid yet. Status: ' . $rx['status']);
            }
            
            $conn->begin_transaction();
            try {
                // Get medications for this prescription with medicine_id
                $stmt = $conn->prepare("
                    SELECT pi.medicine_id, pi.medicine_name, pi.quantity, m.stock, m.name as med_name
                    FROM prescription_items_v2 pi
                    LEFT JOIN medicines m ON pi.medicine_id = m.id OR pi.medicine_name = m.name
                    WHERE pi.prescription_id = ?
                ");
                $stmt->bind_param("i", $prescriptionId);
                $stmt->execute();
                $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                foreach ($items as $item) {
                    $medicineId = $item['medicine_id'];
                    $medName = $item['med_name'] ?? $item['medicine_name'];
                    $qty = intval($item['quantity']);
                    
                    if (!$medicineId) {
                        throw new Exception("Medicine not found in inventory: $medName");
                    }
                    
                    // Check stock using medicine_id
                    $stmt = $conn->prepare("SELECT id, stock, name FROM medicines WHERE id = ? FOR UPDATE");
                    $stmt->bind_param("i", $medicineId);
                    $stmt->execute();
                    $med = $stmt->get_result()->fetch_assoc();
                    
                    if (!$med || $med['stock'] < $qty) {
                        throw new Exception("Insufficient stock for {$med['name']}. Available: " . ($med['stock'] ?? 0) . ", Required: $qty");
                    }
                    
                    // Deduct stock using medicine_id
                    $stmt = $conn->prepare("UPDATE medicines SET stock = stock - ? WHERE id = ?");
                    $stmt->bind_param("ii", $qty, $medicineId);
                    $stmt->execute();
                }
                
                // Update prescription status
                $stmt = $conn->prepare("UPDATE prescriptions_v2 SET status = 'Dispensed', dispensed_at = NOW() WHERE id = ?");
                $stmt->bind_param("i", $prescriptionId);
                $stmt->execute();
                
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Items dispensed and stock updated.']);
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;

        // ==================================================
        // Stage 4: Complete Prescription (Dispensed -> Completed)
        // ==================================================
        case 'complete_prescription':
            if ($userRole !== 'pharmacy') {
                throw new Exception('Unauthorized');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $prescriptionId = $input['prescription_id'] ?? 0;
            
            if (!$prescriptionId) throw new Exception('Invalid prescription ID');
            
            // Validate status
            $stmt = $conn->prepare("SELECT status FROM prescriptions_v2 WHERE id = ? AND pharmacy_id = ?");
            $stmt->bind_param("ii", $prescriptionId, $userId);
            $stmt->execute();
            $rx = $stmt->get_result()->fetch_assoc();
            
            if (!$rx || $rx['status'] !== 'Dispensed') {
                throw new Exception('Invalid transition. Current status: ' . ($rx['status'] ?? 'None'));
            }
            
            // Finalize
            $stmt = $conn->prepare("UPDATE prescriptions_v2 SET status = 'Completed', completed_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $prescriptionId);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Prescription completed and moved to history.']);
            } else {
                throw new Exception('Failed to update prescription status');
            }
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
        // Get completed prescriptions (History)
        // ==================================================
        case 'get_history':
            if ($userRole !== 'pharmacy') throw new Exception('Unauthorized');
            
            $stmt = $conn->prepare("
                SELECT p.*, pat.full_name as patient_name, doc.full_name as doctor_name
                FROM prescriptions_v2 p
                JOIN users pat ON p.patient_id = pat.id
                JOIN users doc ON p.doctor_id = doc.id
                WHERE p.pharmacy_id = ? AND p.status = 'Completed'
                ORDER BY p.completed_at DESC
                LIMIT 50
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode(['success' => true, 'history' => $result]);
            break;

        // ==================================================
        // Get dashboard statistics
        // ==================================================
        case 'get_dashboard_stats':
            if ($userRole !== 'pharmacy') {
                throw new Exception('Only pharmacies can access this');
            }
            
            // New Prescriptions (Pending or sent_to_pharmacy)
            $pendingCount = $conn->query("
                SELECT COUNT(*) as count FROM prescriptions_v2 
                WHERE pharmacy_id = $userId 
                AND status = 'Pending'
                AND ordered_at IS NOT NULL
            ")->fetch_assoc()['count'];
            
            // In Process (Verified, Awaiting Payment, Paid, Dispensed)
            $activeCount = $conn->query("
                SELECT COUNT(*) as count FROM prescriptions_v2 
                WHERE pharmacy_id = $userId 
                AND status IN ('Verified', 'Awaiting Payment', 'Paid', 'Dispensed')
                AND ordered_at IS NOT NULL
            ")->fetch_assoc()['count'];
            
            // Completed today
            $completedToday = $conn->query("
                SELECT COUNT(*) as count FROM prescriptions_v2 
                WHERE pharmacy_id = $userId 
                AND status = 'Completed' 
                AND DATE(completed_at) = CURDATE()
            ")->fetch_assoc()['count'];
            
            // Low stock alerts (using 5 as a default threshold from medicines table)
            $lowStockCount = $conn->query("
                SELECT COUNT(*) as count FROM medicines 
                WHERE stock <= low_stock_threshold
            ")->fetch_assoc()['count'];
            
            // Earnings (Today and Month)
            $todayEarnings = $conn->query("
                SELECT SUM(net_amount) as total FROM pharmacy_earnings 
                WHERE pharmacy_id = $userId AND DATE(created_at) = CURDATE()
            ")->fetch_assoc()['total'] ?? 0;
            
            $monthEarnings = $conn->query("
                SELECT SUM(net_amount) as total FROM pharmacy_earnings 
                WHERE pharmacy_id = $userId AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
            ")->fetch_assoc()['total'] ?? 0;
            
            // Total orders and rate
            $totalOrders = $conn->query("SELECT COUNT(*) as count FROM prescriptions_v2 WHERE pharmacy_id = $userId")->fetch_assoc()['count'];
            $fulfilledOrders = $conn->query("SELECT COUNT(*) as count FROM prescriptions_v2 WHERE pharmacy_id = $userId AND status = 'Completed'")->fetch_assoc()['count'];
            $fulfillmentRate = $totalOrders > 0 ? ($fulfilledOrders / $totalOrders) * 100 : 0;
            
            ob_end_clean(); // Clean buffer before JSON output
            echo json_encode([
                'success' => true,
                'stats' => [
                    'pending_prescriptions' => intval($pendingCount),
                    'active_orders' => intval($activeCount),
                    'completed_today' => intval($completedToday),
                    'low_stock_alerts' => intval($lowStockCount),
                    'today_earnings' => floatval($todayEarnings),
                    'month_earnings' => floatval($monthEarnings),
                    'total_orders' => intval($totalOrders),
                    'fulfillment_rate' => round($fulfillmentRate, 2)
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
        
        // ==================================================
        // Get medicines inventory
        // ==================================================
        case 'get_medicines_inventory':
            if ($userRole !== 'pharmacy') {
                throw new Exception('Only pharmacies can access inventory');
            }
            
            $search = $_GET['search'] ?? '';
            $category = $_GET['category'] ?? '';
            $lowStockOnly = ($_GET['low_stock_only'] ?? 'false') === 'true';
            
            $query = "SELECT * FROM medicines WHERE 1=1";
            $params = [];
            $types = '';
            
            if (!empty($search)) {
                $query .= " AND (name LIKE ? OR generic_name LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $types .= 'ss';
            }
            
            if (!empty($category)) {
                $query .= " AND category = ?";
                $params[] = $category;
                $types .= 's';
            }
            
            if ($lowStockOnly) {
                $query .= " AND stock <= low_stock_threshold";
            }
            
            $query .= " ORDER BY name ASC";
            
            if (!empty($params)) {
                $stmt = $conn->prepare($query);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $conn->query($query);
            }
            
            $medicines = [];
            while ($row = $result->fetch_assoc()) {
                $medicines[] = $row;
            }
            
            // Get distinct categories for filter dropdown
            $categories = $conn->query("SELECT DISTINCT category FROM medicines WHERE category IS NOT NULL ORDER BY category")->fetch_all(MYSQLI_ASSOC);
            
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'medicines' => $medicines,
                'categories' => array_column($categories, 'category'),
                'count' => count($medicines)
            ]);
            break;
        
        // ==================================================
        // Update medicine stock
        // ==================================================
        case 'update_medicine_stock':
            if ($userRole !== 'pharmacy') {
                throw new Exception('Only pharmacies can update stock');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $medicineId = $input['medicine_id'] ?? 0;
            $newStock = $input['new_stock'] ?? null;
            
            if (!$medicineId || $newStock === null) {
                throw new Exception('Medicine ID and new stock level required');
            }
            
            if ($newStock < 0) {
                throw new Exception('Stock cannot be negative');
            }
            
            $stmt = $conn->prepare("UPDATE medicines SET stock = ? WHERE id = ?");
            $stmt->bind_param("ii", $newStock, $medicineId);
            
            if ($stmt->execute()) {
                // Get updated medicine details
                $stmt = $conn->prepare("SELECT * FROM medicines WHERE id = ?");
                $stmt->bind_param("i", $medicineId);
                $stmt->execute();
                $medicine = $stmt->get_result()->fetch_assoc();
                
                ob_end_clean();
                echo json_encode([
                    'success' => true,
                    'message' => 'Stock updated successfully',
                    'medicine' => $medicine
                ]);
            } else {
                throw new Exception('Failed to update stock');
            }
            break;
        
        // ==================================================
        // Add new medicine
        // ==================================================
        case 'add_new_medicine':
            if ($userRole !== 'pharmacy') {
                throw new Exception('Only pharmacies can add medicines');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            $name = trim($input['name'] ?? '');
            $genericName = trim($input['generic_name'] ?? '');
            $category = trim($input['category'] ?? '');
            $price = floatval($input['price'] ?? 0);
            $stock = intval($input['stock'] ?? 0);
            $unit = trim($input['unit'] ?? 'tablet');
            $manufacturer = trim($input['manufacturer'] ?? '');
            $description = trim($input['description'] ?? '');
            $lowStockThreshold = intval($input['low_stock_threshold'] ?? 10);
            
            // Validate required fields
            if (empty($name)) {
                throw new Exception('Medicine name is required');
            }
            
            if ($price < 0) {
                throw new Exception('Price cannot be negative');
            }
            
            if ($stock < 0) {
                throw new Exception('Stock cannot be negative');
            }
            
            // Check if medicine already exists
            $stmt = $conn->prepare("SELECT id FROM medicines WHERE name = ? LIMIT 1");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            
            if ($existing) {
                throw new Exception('A medicine with this name already exists');
            }
            
            // Insert new medicine
            $stmt = $conn->prepare("
                INSERT INTO medicines (name, generic_name, category, price, stock, unit, manufacturer, description, low_stock_threshold, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("sssdiissi", $name, $genericName, $category, $price, $stock, $unit, $manufacturer, $description, $lowStockThreshold);
            
            if ($stmt->execute()) {
                $newMedicineId = $conn->insert_id;
                
                // Get the newly created medicine
                $stmt = $conn->prepare("SELECT * FROM medicines WHERE id = ?");
                $stmt->bind_param("i", $newMedicineId);
                $stmt->execute();
                $medicine = $stmt->get_result()->fetch_assoc();
                
                ob_end_clean();
                echo json_encode([
                    'success' => true,
                    'message' => 'Medicine added successfully',
                    'medicine' => $medicine
                ]);
            } else {
                throw new Exception('Failed to add medicine');
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
?>
