<?php
/**
 * Payment Gateway API
 * Handle payments for consultations and medications
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

try {
    switch ($action) {
        
        // ==================================================
        // Initiate payment
        // ==================================================
        case 'initiate_payment':
            $input = json_decode(file_get_contents('php://input'), true);
            
            $transactionType = $input['transaction_type'] ?? ''; // 'consultation_fee' or 'medication_payment'
            $relatedId = $input['related_id'] ?? 0;
            $amount = floatval($input['amount'] ?? 0);
            $paymentMethod = $input['payment_method'] ?? 'card';
            
            if (!$transactionType || !$relatedId || $amount <= 0) {
                throw new Exception('Invalid payment parameters');
            }
            
            // Validate payment method
            $validMethods = ['card', 'upi', 'netbanking', 'wallet'];
            if (!in_array($paymentMethod, $validMethods)) {
                throw new Exception('Invalid payment method');
            }
            
            // Generate transaction number
            $transactionNumber = 'TXN' . time() . rand(1000, 9999);
            
            // Determine related type
            $relatedType = null;
            if ($transactionType === 'consultation_fee') {
                $relatedType = 'consultation';
                
                // Verify consultation exists and belongs to user
                $stmt = $conn->prepare("SELECT id FROM consultations WHERE id = ? AND patient_id = ?");
                $stmt->bind_param("ii", $relatedId, $userId);
                $stmt->execute();
                if ($stmt->get_result()->num_rows === 0) {
                    throw new Exception('Invalid consultation');
                }
                
            } elseif ($transactionType === 'medication_payment') {
                $relatedType = 'prescription_order';
                
                // Verify prescription order exists and belongs to user
                $stmt = $conn->prepare("SELECT id FROM prescription_orders WHERE id = ? AND patient_id = ?");
                $stmt->bind_param("ii", $relatedId, $userId);
                $stmt->execute();
                if ($stmt->get_result()->num_rows === 0) {
                    throw new Exception('Invalid prescription order');
                }
            }
            
            // Create payment transaction
            $stmt = $conn->prepare("
                INSERT INTO payment_transactions (
                    transaction_number, user_id, transaction_type, related_id,
                    related_type, amount, currency, payment_method,
                    payment_gateway, status
                ) VALUES (?, ?, ?, ?, ?, ?, 'INR', ?, 'simulator', 'pending')
            ");
            
            $stmt->bind_param(
                "sisisds",
                $transactionNumber, $userId, $transactionType, $relatedId,
                $relatedType, $amount, $paymentMethod
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to initiate payment');
            }
            
            $transactionId = $stmt->insert_id;
            
            // In production, redirect to actual payment gateway
            // For now, simulate payment gateway response
            echo json_encode([
                'success' => true,
                'transaction_id' => $transactionId,
                'transaction_number' => $transactionNumber,
                'payment_url' => "simulate_payment.php?txn=$transactionId",
                'message' => 'Payment initiated. Redirecting to payment gateway...'
            ]);
            break;
        
        // ==================================================
        // Process payment (simulated gateway callback)
        // ==================================================
        case 'process_payment':
            $transactionId = $_POST['transaction_id'] ?? 0;
            $gatewayTxnId = $_POST['gateway_txn_id'] ?? 'SIM' . time();
            $status = $_POST['status'] ?? 'success'; // 'success' or 'failed'
            
            // Get transaction details
            $stmt = $conn->prepare("
                SELECT * FROM payment_transactions WHERE id = ?
            ");
            $stmt->bind_param("i", $transactionId);
            $stmt->execute();
            $transaction = $stmt->get_result()->fetch_assoc();
            
            if (!$transaction) {
                throw new Exception('Transaction not found');
            }
            
            if ($status === 'success') {
                // Update transaction status
                $stmt = $conn->prepare("
                    UPDATE payment_transactions
                    SET status = 'completed',
                        gateway_transaction_id = ?,
                        completed_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->bind_param("si", $gatewayTxnId, $transactionId);
                $stmt->execute();
                
                // Process revenue split
                processRevenueplit($conn, $transaction);
                
                // Update related records
                if ($transaction['transaction_type'] === 'consultation_fee') {
                    $conn->query("
                        UPDATE consultations
                        SET payment_status = 'paid'
                        WHERE id = {$transaction['related_id']}
                    ");
                    
                } elseif ($transaction['transaction_type'] === 'medication_payment') {
                    $conn->query("
                        UPDATE prescription_orders
                        SET payment_status = 'paid'
                        WHERE id = {$transaction['related_id']}
                    ");
                }
                
                // Send notification
                $notifService = getNotificationService();
                $notifService->send($userId, 'all',
                    'Payment Successful',
                    "Your payment of ₹{$transaction['amount']} was successful. Transaction ID: {$transaction['transaction_number']}",
                    ['notification_type' => 'payment_success']
                );
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment processed successfully'
                ]);
                
            } else {
                // Payment failed
                $failureReason = $_POST['failure_reason'] ?? 'Payment declined';
                
                $stmt = $conn->prepare("
                    UPDATE payment_transactions
                    SET status = 'failed', failure_reason = ?
                    WHERE id = ?
                ");
                
                $stmt->bind_param("si", $failureReason, $transactionId);
                $stmt->execute();
                
                echo json_encode([
                    'success' => false,
                    'error' => 'Payment failed: ' . $failureReason
                ]);
            }
            break;
        
        // ==================================================
        // Get payment history
        // ==================================================
        case 'get_payment_history':
            $limit = intval($_GET['limit'] ?? 20);
            $offset = intval($_GET['offset'] ?? 0);
            
            $stmt = $conn->prepare("
                SELECT * FROM payment_transactions
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $stmt->bind_param("iii", $userId, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $transactions = [];
            while ($row = $result->fetch_assoc()) {
                $transactions[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'transactions' => $transactions
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

/**
 * Process revenue split for completed payment
 */
function processRevenueSplit($conn, $transaction) {
    // Get revenue split configuration
    $type = ($transaction['transaction_type'] === 'consultation_fee') ? 'consultation' : 'medication';
    
    $stmt = $conn->prepare("
        SELECT * FROM revenue_splits
        WHERE service_type = ? AND is_active = TRUE
        ORDER BY effective_from DESC LIMIT 1
    ");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $split = $stmt->get_result()->fetch_assoc();
    
    if (!$split) {
        // Default split if not configured
        $platformCommission = $transaction['amount'] * 0.10; // 10%
        $providerAmount = $transaction['amount'] * 0.90; // 90%
    } else {
        $platformCommission = $transaction['amount'] * ($split['platform_commission_percent'] / 100);
        
        if ($type === 'consultation') {
            $providerAmount = $transaction['amount'] * ($split['doctor_percent'] / 100);
        } else {
            $providerAmount = $transaction['amount'] * ($split['pharmacy_percent'] / 100);
        }
    }
    
    // Record earnings
    if ($type === 'consultation') {
        // Get doctor from consultation
       $result = $conn->query("
            SELECT doctor_id FROM consultations WHERE id = {$transaction['related_id']}
        ");
        $consultation = $result->fetch_assoc();
        
        if ($consultation && $consultation['doctor_id']) {
            $stmt = $conn->prepare("
                INSERT INTO doctor_earnings (
                    doctor_id, consultation_id, gross_amount,
                    platform_commission_percent, platform_commission_amount,
                    net_amount, payment_status
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            $commissionPercent = $split['platform_commission_percent'] ?? 10.00;
            $stmt->bind_param(
                "iidddd",
                $consultation['doctor_id'], $transaction['related_id'],
                $transaction['amount'], $commissionPercent,
                $platformCommission, $providerAmount
            );
            $stmt->execute();
        }
        
    } else {
        // Get pharmacy from prescription order
        $result = $conn->query("
            SELECT pharmacy_id FROM prescription_orders WHERE id = {$transaction['related_id']}
        ");
        $order = $result->fetch_assoc();
        
        if ($order && $order['pharmacy_id']) {
            $stmt = $conn->prepare("
                INSERT INTO pharmacy_earnings (
                    pharmacy_id, prescription_order_id, gross_amount,
                    platform_commission_percent, platform_commission_amount,
                    net_amount, payment_status
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            $commissionPercent = $split['platform_commission_percent'] ?? 5.00;
            $stmt->bind_param(
                "iidddd",
                $order['pharmacy_id'], $transaction['related_id'],
                $transaction['amount'], $commissionPercent,
                $platformCommission, $providerAmount
            );
            $stmt->execute();
        }
    }
}
