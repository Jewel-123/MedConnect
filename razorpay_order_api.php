<?php
/**
 * Razorpay Order Creation API
 * Creates orders with Razorpay before payment
 */

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'db.php';
require_once 'razorpay_config.php';

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
        // Create Razorpay Order
        // ==================================================
        case 'create_order':
            $input = json_decode(file_get_contents('php://input'), true);
            
            $amount = floatval($input['amount'] ?? 0);
            $transactionType = $input['transaction_type'] ?? ''; // 'consultation_fee' or 'medication_payment'
            $relatedId = $input['related_id'] ?? 0;
            $notes = $input['notes'] ?? [];
            
            if ($amount <= 0) {
                throw new Exception('Invalid amount');
            }
            
            if (!$transactionType || !$relatedId) {
                throw new Exception('Invalid transaction details');
            }
            
            // Validate related entity exists
            $relatedType = null;
            if ($transactionType === 'consultation_fee') {
                $relatedType = 'consultation';
                $stmt = $conn->prepare("SELECT id FROM consultations WHERE id = ? AND patient_id = ?");
                $stmt->bind_param("ii", $relatedId, $userId);
                $stmt->execute();
                if ($stmt->get_result()->num_rows === 0) {
                    throw new Exception('Invalid consultation');
                }
            } elseif ($transactionType === 'medication_payment') {
                $relatedType = 'prescription_order';
                $stmt = $conn->prepare("SELECT id FROM prescription_orders WHERE id = ? AND patient_id = ?");
                $stmt->bind_param("ii", $relatedId, $userId);
                $stmt->execute();
                if ($stmt->get_result()->num_rows === 0) {
                    throw new Exception('Invalid prescription order');
                }
            } else {
                throw new Exception('Invalid transaction type');
            }
            
            // Generate transaction number and receipt
            $transactionNumber = 'TXN' . time() . rand(1000, 9999);
            $receiptId = 'RCPT' . time() . rand(100, 999);
            
            // Convert amount to paise
            $amountInPaise = convertToPaise($amount);
            
            // Check if test mode is enabled
            if (defined('PAYMENT_TEST_MODE') && PAYMENT_TEST_MODE === true) {
                // Simulate Razorpay order for testing
                $razorpayOrder = [
                    'id' => 'order_test_' . time() . rand(1000, 9999),
                    'amount' => $amountInPaise,
                    'currency' => RAZORPAY_CURRENCY,
                    'status' => 'created'
                ];
                
                error_log("TEST MODE: Simulated Razorpay order created: " . $razorpayOrder['id']);
            } else {
                // Real Razorpay API call
                // Prepare Razorpay order data
                $orderData = [
                    'amount' => $amountInPaise,
                    'currency' => RAZORPAY_CURRENCY,
                    'receipt' => $receiptId,
                    'notes' => array_merge([
                        'transaction_type' => $transactionType,
                        'related_id' => $relatedId,
                        'user_id' => $userId,
                        'transaction_number' => $transactionNumber
                    ], $notes)
                ];
                
                // Create order with Razorpay API
                $ch = curl_init(RAZORPAY_API_URL . '/orders');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: ' . getRazorpayAuthHeader()
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode !== 200) {
                    $error = json_decode($response, true);
                    throw new Exception('Razorpay API error: ' . ($error['error']['description'] ?? 'Unknown error'));
                }
                
                $razorpayOrder = json_decode($response, true);
            }
            
            // Store transaction in database
            $stmt = $conn->prepare("
                INSERT INTO payment_transactions (
                    transaction_number, user_id, transaction_type, related_id,
                    related_type, amount, currency, payment_method,
                    payment_gateway, razorpay_order_id, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'card', 'razorpay', ?, 'pending')
            ");
            
            $stmt->bind_param(
                "sisisds s",
                $transactionNumber, $userId, $transactionType, $relatedId,
                $relatedType, $amount, RAZORPAY_CURRENCY, $razorpayOrder['id']
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to store transaction');
            }
            
            $transactionId = $stmt->insert_id;
            
            echo json_encode([
                'success' => true,
                'transaction_id' => $transactionId,
                'transaction_number' => $transactionNumber,
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => $amount,
                'amount_paise' => $amountInPaise,
                'currency' => RAZORPAY_CURRENCY,
                'key_id' => RAZORPAY_KEY_ID
            ]);
            break;
        
        // ==================================================
        // Get order status
        // ==================================================
        case 'get_order_status':
            $transactionId = $_GET['transaction_id'] ?? 0;
            
            $stmt = $conn->prepare("
                SELECT * FROM payment_transactions
                WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param("ii", $transactionId, $userId);
            $stmt->execute();
            $transaction = $stmt->get_result()->fetch_assoc();
            
            if (!$transaction) {
                throw new Exception('Transaction not found');
            }
            
            echo json_encode([
                'success' => true,
                'transaction' => $transaction
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
