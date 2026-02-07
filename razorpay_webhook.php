<?php
/**
 * Razorpay Webhook Handler
 * Processes payment events from Razorpay
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'db.php';
require_once 'razorpay_config.php';
require_once 'notification_service.php';

// Get webhook payload
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

// Log webhook for debugging
file_put_contents('razorpay_webhook_log.txt', date('Y-m-d H:i:s') . " - Webhook received\n" . $payload . "\n\n", FILE_APPEND);

try {
    // Verify webhook signature
    if (!verifyWebhookSignature($payload, $signature)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid signature']);
        exit;
    }
    
    $event = json_decode($payload, true);
    
    if (!$event || !isset($event['event'])) {
        throw new Exception('Invalid webhook payload');
    }
    
    $eventType = $event['event'];
    $paymentData = $event['payload']['payment']['entity'] ?? null;
    
    if (!$paymentData) {
        throw new Exception('No payment data in webhook');
    }
    
    $razorpayOrderId = $paymentData['order_id'] ?? null;
    $razorpayPaymentId = $paymentData['id'] ?? null;
    $amount = $paymentData['amount'] ?? 0;
    $status = $paymentData['status'] ?? '';
    
    if (!$razorpayOrderId) {
        throw new Exception('No order ID in payment data');
    }
    
    // Find transaction by Razorpay order ID
    $stmt = $conn->prepare("
        SELECT * FROM payment_transactions
        WHERE razorpay_order_id = ?
    ");
    $stmt->bind_param("s", $razorpayOrderId);
    $stmt->execute();
    $transaction = $stmt->get_result()->fetch_assoc();
    
    if (!$transaction) {
        throw new Exception('Transaction not found for order: ' . $razorpayOrderId);
    }
    
    // Process based on event type
    switch ($eventType) {
        
        case 'payment.captured':
            // Payment successful
            if ($transaction['status'] === 'completed') {
                // Already processed, avoid duplicate processing
                echo json_encode(['success' => true, 'message' => 'Already processed']);
                exit;
            }
            
            // Update transaction status
            $stmt = $conn->prepare("
                UPDATE payment_transactions
                SET status = 'completed',
                    razorpay_payment_id = ?,
                    gateway_transaction_id = ?,
                    webhook_received_at = NOW(),
                    completed_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("ssi", $razorpayPaymentId, $razorpayPaymentId, $transaction['id']);
            $stmt->execute();
            
            // Process revenue split and update related records
            processPaymentSuccess($conn, $transaction, $razorpayPaymentId);
            
            // Send notification
            $notifService = getNotificationService();
            $notifService->send(
                $transaction['user_id'],
                'all',
                'Payment Successful',
                "Your payment of ₹{$transaction['amount']} was successful. Transaction ID: {$transaction['transaction_number']}",
                ['notification_type' => 'payment_success']
            );
            
            echo json_encode(['success' => true, 'message' => 'Payment processed successfully']);
            break;
        
        case 'payment.failed':
            // Payment failed
            $errorCode = $paymentData['error_code'] ?? 'UNKNOWN';
            $errorDescription = $paymentData['error_description'] ?? 'Payment failed';
            
            $stmt = $conn->prepare("
                UPDATE payment_transactions
                SET status = 'failed',
                    razorpay_payment_id = ?,
                    failure_reason = ?,
                    webhook_received_at = NOW()
                WHERE id = ?
            ");
            
            $failureReason = "$errorCode: $errorDescription";
            $stmt->bind_param("ssi", $razorpayPaymentId, $failureReason, $transaction['id']);
            $stmt->execute();
            
            // Send notification
            $notifService = getNotificationService();
            $notifService->send(
                $transaction['user_id'],
                'all',
                'Payment Failed',
                "Your payment of ₹{$transaction['amount']} failed. Reason: $errorDescription",
                ['notification_type' => 'payment_failed']
            );
            
            echo json_encode(['success' => true, 'message' => 'Payment failure processed']);
            break;
        
        case 'order.paid':
            // Order paid confirmation
            echo json_encode(['success' => true, 'message' => 'Order paid event received']);
            break;
        
        default:
            echo json_encode(['success' => true, 'message' => 'Event type not handled: ' . $eventType]);
    }
    
} catch (Exception $e) {
    file_put_contents('razorpay_webhook_log.txt', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Process successful payment
 */
function processPaymentSuccess($conn, $transaction, $razorpayPaymentId) {
    // Update related records based on transaction type
    if ($transaction['transaction_type'] === 'consultation_fee') {
        // Update consultation payment status
        $conn->query("
            UPDATE consultations
            SET payment_status = 'paid'
            WHERE id = {$transaction['related_id']}
        ");
        
        // Process doctor earnings
        processRevenueSplit($conn, $transaction, 'consultation');
        
    } elseif ($transaction['transaction_type'] === 'medication_payment' || $transaction['transaction_type'] === 'prescription_payment') {
        // Update prescription order payment status
        $conn->query("
            UPDATE prescription_orders
            SET payment_status = 'paid',
                order_status = 'completed',
                completed_at = NOW()
            WHERE id = {$transaction['related_id']}
        ");
        
        // Update prescription status to completed
        $conn->query("
            UPDATE prescriptions_v2 p
            JOIN prescription_orders po ON p.id = po.prescription_id
            SET p.status = 'completed',
                p.completed_at = NOW()
            WHERE po.id = {$transaction['related_id']}
        ");
        
        // Process pharmacy earnings
        processRevenueSplit($conn, $transaction, 'medication');
    }
}

/**
 * Process revenue split for completed payment
 */
function processRevenueSplit($conn, $transaction, $type) {
    // Get revenue split configuration
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
        $commissionPercent = 10.00;
    } else {
        $platformCommission = $transaction['amount'] * ($split['platform_commission_percent'] / 100);
        
        if ($type === 'consultation') {
            $providerAmount = $transaction['amount'] * ($split['doctor_percent'] / 100);
        } else {
            $providerAmount = $transaction['amount'] * ($split['pharmacy_percent'] / 100);
        }
        $commissionPercent = $split['platform_commission_percent'];
    }
    
    // Record earnings
    if ($type === 'consultation') {
        // Get doctor from consultation
        $result = $conn->query("
            SELECT doctor_id FROM consultations WHERE id = {$transaction['related_id']}
        ");
        $consultation = $result->fetch_assoc();
        
        if ($consultation && $consultation['doctor_id']) {
            // Check if earnings already recorded
            $check = $conn->query("
                SELECT id FROM doctor_earnings WHERE consultation_id = {$transaction['related_id']}
            ");
            
            if ($check->num_rows === 0) {
                $stmt = $conn->prepare("
                    INSERT INTO doctor_earnings (
                        doctor_id, consultation_id, gross_amount,
                        platform_commission_percent, platform_commission_amount,
                        net_amount, payment_status
                    ) VALUES (?, ?, ?, ?, ?, ?, 'pending')
                ");
                
                $stmt->bind_param(
                    "iidddd",
                    $consultation['doctor_id'], $transaction['related_id'],
                    $transaction['amount'], $commissionPercent,
                    $platformCommission, $providerAmount
                );
                $stmt->execute();
            }
        }
        
    } else {
        // Get pharmacy from prescription order
        $result = $conn->query("
            SELECT pharmacy_id FROM prescription_orders WHERE id = {$transaction['related_id']}
        ");
        $order = $result->fetch_assoc();
        
        if ($order && $order['pharmacy_id']) {
            // Check if earnings already recorded
            $check = $conn->query("
                SELECT id FROM pharmacy_earnings WHERE prescription_order_id = {$transaction['related_id']}
            ");
            
            if ($check->num_rows === 0) {
                $stmt = $conn->prepare("
                    INSERT INTO pharmacy_earnings (
                        pharmacy_id, prescription_order_id, gross_amount,
                        platform_commission_percent, platform_commission_amount,
                        net_amount, payment_status
                    ) VALUES (?, ?, ?, ?, ?, ?, 'pending')
                ");
                
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
}