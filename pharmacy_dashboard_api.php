<?php
/**
 * Pharmacy Dashboard API
 * Real-time data endpoints for pharmacy dashboard
 */

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'db.php';

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacy') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$action = $_GET['action'] ?? '';
$pharmacyId = $_SESSION['user_id'];

try {
    switch ($action) {
        
        // ==================================================
        // Get this month earnings
        // ==================================================
        case 'get_month_earnings':
            $stmt = $conn->prepare("
                SELECT 
                    COALESCE(SUM(gross_amount), 0) as total_gross,
                    COALESCE(SUM(platform_commission_amount), 0) as total_commission,
                    COALESCE(SUM(net_amount), 0) as total_net,
                    COUNT(*) as order_count
                FROM pharmacy_earnings
                WHERE pharmacy_id = ?
                AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
            ");
            
            $stmt->bind_param("i", $pharmacyId);
            $stmt->execute();
            $earnings = $stmt->get_result()->fetch_assoc();
            
            echo json_encode([
                'success' => true,
                'earnings' => [
                    'gross' => floatval($earnings['total_gross']),
                    'commission' => floatval($earnings['total_commission']),
                    'net' => floatval($earnings['total_net']),
                    'order_count' => intval($earnings['order_count']),
                    'period' => 'This Month'
                ]
            ]);
            break;
        
        // ==================================================
        // Get total earnings (all time)
        // ==================================================
        case 'get_total_earnings':
            $stmt = $conn->prepare("
                SELECT 
                    COALESCE(SUM(gross_amount), 0) as total_gross,
                    COALESCE(SUM(platform_commission_amount), 0) as total_commission,
                    COALESCE(SUM(net_amount), 0) as total_net,
                    COUNT(*) as order_count,
                    MIN(created_at) as first_earning_date
                FROM pharmacy_earnings
                WHERE pharmacy_id = ?
            ");
            
            $stmt->bind_param("i", $pharmacyId);
            $stmt->execute();
            $earnings = $stmt->get_result()->fetch_assoc();
            
            // Get average commission percentage
            $avgCommission = 0;
            if ($earnings['total_gross'] > 0) {
                $avgCommission = ($earnings['total_commission'] / $earnings['total_gross']) * 100;
            }
            
            echo json_encode([
                'success' => true,
                'earnings' => [
                    'gross' => floatval($earnings['total_gross']),
                    'commission' => floatval($earnings['total_commission']),
                    'net' => floatval($earnings['total_net']),
                    'order_count' => intval($earnings['order_count']),
                    'avg_commission_percent' => round($avgCommission, 2),
                    'first_earning_date' => $earnings['first_earning_date'],
                    'period' => 'All Time'
                ]
            ]);
            break;
        
        // ==================================================
        // Get active orders count
        // ==================================================
        case 'get_active_orders_count':
            // Count orders by status
            $stmt = $conn->prepare("
                SELECT 
                    order_status,
                    COUNT(*) as count
                FROM prescription_orders
                WHERE pharmacy_id = ?
                AND order_status NOT IN ('completed', 'cancelled')
                GROUP BY order_status
            ");
            
            $stmt->bind_param("i", $pharmacyId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $statusCounts = [
                'pending' => 0,
                'accepted' => 0,
                'preparing' => 0,
                'ready' => 0,
                'out_for_delivery' => 0,
                'delivered' => 0
            ];
            
            $totalActive = 0;
            while ($row = $result->fetch_assoc()) {
                $statusCounts[$row['order_status']] = intval($row['count']);
                $totalActive += intval($row['count']);
            }
            
            echo json_encode([
                'success' => true,
                'active_orders' => [
                    'total' => $totalActive,
                    'by_status' => $statusCounts
                ]
            ]);
            break;
        
        // ==================================================
        // Get payment history
        // ==================================================
        case 'get_payment_history':
            $limit = intval($_GET['limit'] ?? 20);
            $offset = intval($_GET['offset'] ?? 0);
            
            // Get payment transactions related to pharmacy orders
            $stmt = $conn->prepare("
                SELECT 
                    pt.*,
                    po.order_number,
                    po.order_status,
                    u.full_name as patient_name,
                    u.email as patient_email
                FROM payment_transactions pt
                JOIN prescription_orders po ON pt.related_id = po.id
                JOIN users u ON pt.user_id = u.id
                WHERE po.pharmacy_id = ?
                AND pt.transaction_type = 'medication_payment'
                ORDER BY pt.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $stmt->bind_param("iii", $pharmacyId, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $payments = [];
            while ($row = $result->fetch_assoc()) {
                $payments[] = [
                    'id' => $row['id'],
                    'transaction_number' => $row['transaction_number'],
                    'order_number' => $row['order_number'],
                    'patient_name' => $row['patient_name'],
                    'patient_email' => $row['patient_email'],
                    'amount' => floatval($row['amount']),
                    'status' => $row['status'],
                    'payment_method' => $row['payment_method'],
                    'razorpay_payment_id' => $row['razorpay_payment_id'],
                    'order_status' => $row['order_status'],
                    'created_at' => $row['created_at'],
                    'completed_at' => $row['completed_at']
                ];
            }
            
            // Get total count
            $countStmt = $conn->prepare("
                SELECT COUNT(*) as total
                FROM payment_transactions pt
                JOIN prescription_orders po ON pt.related_id = po.id
                WHERE po.pharmacy_id = ?
                AND pt.transaction_type = 'medication_payment'
            ");
            $countStmt->bind_param("i", $pharmacyId);
            $countStmt->execute();
            $totalCount = $countStmt->get_result()->fetch_assoc()['total'];
            
            echo json_encode([
                'success' => true,
                'payments' => $payments,
                'pagination' => [
                    'total' => intval($totalCount),
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $totalCount
                ]
            ]);
            break;
        
        // ==================================================
        // Get dashboard summary (all stats at once)
        // ==================================================
        case 'get_dashboard_summary':
            // Month earnings
            $stmt = $conn->prepare("
                SELECT 
                    COALESCE(SUM(net_amount), 0) as month_earnings
                FROM pharmacy_earnings
                WHERE pharmacy_id = ?
                AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
            ");
            $stmt->bind_param("i", $pharmacyId);
            $stmt->execute();
            $monthEarnings = $stmt->get_result()->fetch_assoc()['month_earnings'];
            
            // Total earnings
            $stmt = $conn->prepare("
                SELECT 
                    COALESCE(SUM(net_amount), 0) as total_earnings
                FROM pharmacy_earnings
                WHERE pharmacy_id = ?
            ");
            $stmt->bind_param("i", $pharmacyId);
            $stmt->execute();
            $totalEarnings = $stmt->get_result()->fetch_assoc()['total_earnings'];
            
            // Active orders count
            $stmt = $conn->prepare("
                SELECT COUNT(*) as active_count
                FROM prescription_orders
                WHERE pharmacy_id = ?
                AND order_status NOT IN ('completed', 'cancelled')
            ");
            $stmt->bind_param("i", $pharmacyId);
            $stmt->execute();
            $activeOrders = $stmt->get_result()->fetch_assoc()['active_count'];
            
            // Total orders
            $stmt = $conn->prepare("
                SELECT COUNT(*) as total_orders
                FROM prescription_orders
                WHERE pharmacy_id = ?
            ");
            $stmt->bind_param("i", $pharmacyId);
            $stmt->execute();
            $totalOrders = $stmt->get_result()->fetch_assoc()['total_orders'];
            
            // Pending prescriptions
            $stmt = $conn->prepare("
                SELECT COUNT(*) as pending_count
                FROM prescriptions_v2
                WHERE pharmacy_id = ?
                AND status = 'sent_to_pharmacy'
            ");
            $stmt->bind_param("i", $pharmacyId);
            $stmt->execute();
            $pendingPrescriptions = $stmt->get_result()->fetch_assoc()['pending_count'];
            
            echo json_encode([
                'success' => true,
                'summary' => [
                    'month_earnings' => floatval($monthEarnings),
                    'total_earnings' => floatval($totalEarnings),
                    'active_orders' => intval($activeOrders),
                    'total_orders' => intval($totalOrders),
                    'pending_prescriptions' => intval($pendingPrescriptions)
                ]
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