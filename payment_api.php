<?php
/**
 * Payment Gateway API
 * Handle payments for consultations and medications
 */

// Start output buffering FIRST to catch any stray output from included files
ob_start();

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'db.php';
require_once 'notification_service.php';
require_once 'razorpay_config.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    ob_end_clean(); // Clear any buffered output
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
            
            // Generate transaction number and receipt
            $transactionNumber = 'TXN' . time() . rand(1000, 9999);
            $receiptId = 'RCPT' . time() . rand(100, 999);
            
            // Determine related type
            $relatedType = null;
            if ($transactionType === 'consultation_fee') {
                $relatedType = 'appointment';
                
                // Verify appointment exists and belongs to user, and get doctor_id
                $stmt = $conn->prepare("SELECT id, doctor_id FROM appointments WHERE id = ? AND patient_id = ?");
                $stmt->bind_param("ii", $relatedId, $userId);
                $stmt->execute();
                $apptResult = $stmt->get_result();
                $appt = $apptResult->fetch_assoc();
                
                if (!$appt) {
                    throw new Exception('Invalid appointment');
                }
                $transactionDoctorId = $appt['doctor_id'];
                
            } elseif ($transactionType === 'medication_payment') {
                $relatedType = 'prescription_order';
                
                // Verify prescription order exists and belongs to user
                $stmt = $conn->prepare("SELECT id FROM prescription_orders WHERE id = ? AND patient_id = ?");
                $stmt->bind_param("ii", $relatedId, $userId);
                $stmt->execute();
                if ($stmt->get_result()->num_rows === 0) {
                    throw new Exception('Invalid prescription order');
                }
                $transactionDoctorId = null; // Pharmacy related
            }
            
            // Convert amount to paise for Razorpay
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
                error_log("TEST MODE: Order amount: " . $amountInPaise . " paise (₹" . $amount . ")");
            } else {
                // Real Razorpay API call
                // Prepare Razorpay order data
                $orderData = [
                    'amount' => $amountInPaise,
                    'currency' => RAZORPAY_CURRENCY,
                    'receipt' => $receiptId,
                    'notes' => [
                        'transaction_type' => $transactionType,
                        'related_id' => $relatedId,
                        'user_id' => $userId,
                        'transaction_number' => $transactionNumber
                    ]
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
                $curlError = curl_error($ch);
                
                // Debug logging
                if ($httpCode !== 200) {
                    error_log("Razorpay API Error - HTTP Code: $httpCode");
                    error_log("Razorpay Response: $response");
                    error_log("Using Key ID: " . RAZORPAY_KEY_ID);
                    if ($curlError) {
                        error_log("cURL Error: $curlError");
                    }
                }
                
                curl_close($ch);
                
                // Check for cURL errors
                if ($curlError) {
                    throw new Exception('Network error: ' . $curlError);
                }
                
                if ($httpCode !== 200) {
                    $error = json_decode($response, true);
                    $errorMsg = 'Razorpay API error: ';
                    
                    if (json_last_error() === JSON_ERROR_NONE && isset($error['error']['description'])) {
                        $errorMsg .= $error['error']['description'];
                    } else {
                        $errorMsg .= 'Authentication failed. Please verify your Razorpay credentials in razorpay_config.php';
                    }
                    
                    throw new Exception($errorMsg);
                }
                
                $razorpayOrder = json_decode($response, true);
            }
            
            // Create payment transaction with Razorpay order ID
            $stmt = $conn->prepare("
                INSERT INTO payment_transactions (
                    transaction_number, user_id, doctor_id, transaction_type, related_id,
                    related_type, amount, currency, payment_method,
                    payment_gateway, razorpay_order_id, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'INR', ?, ?, ?, 'pending')
            ");
            
            $paymentGateway = 'Razorpay';
            $stmt->bind_param(
                "siisdsdsss",
                $transactionNumber,
                $userId,
                $transactionDoctorId,
                $transactionType,
                $relatedId,
                $relatedType,
                $amount,
                $paymentMethod,
                $paymentGateway,
                $razorpayOrder['id']
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to initiate payment');
            }
            
            $transactionId = $stmt->insert_id;
            
            // Return order details for Razorpay checkout
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'transaction_id' => $transactionId,
                'transaction_number' => $transactionNumber,
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => $amount,
                'amount_paise' => $amountInPaise,
                'currency' => RAZORPAY_CURRENCY,
                'key_id' => RAZORPAY_KEY_ID,
                'message' => 'Payment initiated successfully'
            ]);
            break;
        
        // ==================================================
        // Process payment (simulated gateway callback)
        // ==================================================
        case 'process_payment':
            $transactionId = $_POST['transaction_id'] ?? 0;
            $razorpayPaymentId = $_POST['razorpay_payment_id'] ?? '';
            $razorpayOrderId = $_POST['razorpay_order_id'] ?? '';
            $razorpaySignature = $_POST['razorpay_signature'] ?? '';
            $status = $_POST['status'] ?? 'success'; // 'success' or 'failed'
            
            // Use Razorpay payment ID as gateway transaction ID
            $gatewayTxnId = $razorpayPaymentId ?: ('SIM' . time());
            
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
                // Check if test mode is enabled
                $isTestMode = defined('PAYMENT_TEST_MODE') && PAYMENT_TEST_MODE === true;
                $isTestOrder = strpos($razorpayOrderId, 'order_test_') === 0;
                
                error_log("PAYMENT PROCESSING: Test Mode = " . ($isTestMode ? 'YES' : 'NO'));
                error_log("PAYMENT PROCESSING: Test Order = " . ($isTestOrder ? 'YES' : 'NO'));
                error_log("PAYMENT PROCESSING: Order ID = " . $razorpayOrderId);
                error_log("PAYMENT PROCESSING: Transaction ID = " . $transactionId);
                
                // Verify Razorpay signature (skip for test mode)
                if (!$isTestMode && !$isTestOrder) {
                    if ($razorpayPaymentId && $razorpayOrderId && $razorpaySignature) {
                        if (!verifyRazorpaySignature($razorpayOrderId, $razorpayPaymentId, $razorpaySignature)) {
                            throw new Exception('Invalid payment signature - Payment verification failed');
                        }
                    } else {
                        throw new Exception('Missing payment verification parameters');
                    }
                } else {
                    error_log("TEST MODE: Skipping signature verification for test payment");
                }
                
                // Update transaction status with Razorpay details
                error_log("PAYMENT PROCESSING: Updating transaction status to completed");
                $stmt = $conn->prepare("
                    UPDATE payment_transactions
                    SET status = 'completed',
                        gateway_transaction_id = ?,
                        razorpay_payment_id = ?,
                        razorpay_signature = ?,
                        completed_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->bind_param("sssi", $razorpayPaymentId, $razorpayPaymentId, $razorpaySignature, $transactionId);
                
                if (!$stmt->execute()) {
                    error_log("PAYMENT PROCESSING ERROR: Failed to update transaction - " . $stmt->error);
                    throw new Exception('Failed to update transaction status');
                }
                
                $affectedRows = $stmt->affected_rows;
                error_log("PAYMENT PROCESSING: Transaction update affected $affectedRows rows");
                
                if ($affectedRows === 0) {
                    error_log("PAYMENT PROCESSING WARNING: No rows updated for transaction ID $transactionId");
                }
                
                // Process revenue split
                error_log("PAYMENT PROCESSING: Processing revenue split");
                processRevenueSplit($conn, $transaction);
                
                // Update related records
                if ($transaction['transaction_type'] === 'consultation_fee') {
                    error_log("PAYMENT PROCESSING: Updating appointment/consultation payment status");
                    
                    // CRITICAL FIX: Update CONSULTATIONS table (not just appointments)
                    // Check if related_id refers to a consultation
                    $checkConsult = $conn->query("SELECT id FROM consultations WHERE id = {$transaction['related_id']}");
                    
                    if ($checkConsult && $checkConsult->num_rows > 0) {
                        // It's a consultation - update consultations table
                        error_log("PAYMENT PROCESSING: Updating consultation ID {$transaction['related_id']}");
                        $stmt = $conn->prepare("
                            UPDATE consultations
                            SET payment_status = 'paid',
                                payment_transaction_id = ?
                            WHERE id = ?
                        ");
                        $stmt->bind_param("ii", $transactionId, $transaction['related_id']);
                        
                        if (!$stmt->execute()) {
                            error_log("PAYMENT PROCESSING ERROR: Failed to update consultation - " . $stmt->error);
                            throw new Exception('Failed to update consultation status');
                        }
                        
                        $consultationAffected = $stmt->affected_rows;
                        error_log("PAYMENT PROCESSING: Consultation update affected $consultationAffected rows");
                        
                        // Get consultation details for notification
                        $stmt = $conn->prepare("
                            SELECT c.*, u.full_name as patient_name
                            FROM consultations c
                            JOIN users u ON c.patient_id = u.id
                            WHERE c.id = ?
                        ");
                        $stmt->bind_param("i", $transaction['related_id']);
                        $stmt->execute();
                        $consultation = $stmt->get_result()->fetch_assoc();
                        
                        if ($consultation && $consultation['doctor_id']) {
                            // Send notification to doctor
                            $notifService = getNotificationService();
                            $notifService->send(
                                $consultation['doctor_id'],
                                'all',
                                'New Paid Consultation',
                                "New consultation request from {$consultation['patient_name']}. Payment confirmed.",
                                [
                                    'role' => 'doctor',
                                    'notification_type' => 'new_consultation',
                                    'related_id' => $consultation['id']
                                ]
                            );
                        }
                        
                    } else {
                        // It's an appointment - update appointments table
                        error_log("PAYMENT PROCESSING: Updating appointment payment status");
                        $stmt = $conn->prepare("
                            UPDATE appointments
                            SET payment_status = 'paid',
                                payment_transaction_id = ?,
                                status = CASE 
                                    WHEN status = 'booked' THEN 'pending'
                                    ELSE status
                                END
                            WHERE id = ?
                        ");
                        $stmt->bind_param("ii", $transactionId, $transaction['related_id']);
                        
                        if (!$stmt->execute()) {
                            error_log("PAYMENT PROCESSING ERROR: Failed to update appointment - " . $stmt->error);
                            throw new Exception('Failed to update appointment status');
                        }
                        
                        $appointmentAffected = $stmt->affected_rows;
                        error_log("PAYMENT PROCESSING: Appointment update affected $appointmentAffected rows for appointment ID " . $transaction['related_id']);
                        
                        // Get appointment details for notification
                        $stmt = $conn->prepare("
                            SELECT a.*, u.full_name as patient_name
                            FROM appointments a
                            JOIN users u ON a.patient_id = u.id
                            WHERE a.id = ?
                        ");
                        $stmt->bind_param("i", $transaction['related_id']);
                        $stmt->execute();
                        $appointment = $stmt->get_result()->fetch_assoc();
                        
                        if ($appointment) {
                            // NOW send notification to doctor (after payment is completed)
                            $notifService = getNotificationService();
                            $notifService->send(
                                $appointment['doctor_id'],
                                'all',
                                'New Paid Appointment',
                                "New appointment from {$appointment['patient_name']} on {$appointment['scheduled_date']} at {$appointment['scheduled_time']}. Payment confirmed.",
                                [
                                    'role' => 'doctor',
                                    'notification_type' => 'new_consultation',
                                    'related_id' => $appointment['id']
                                ]
                            );
                        }
                    }
                    
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
                
                ob_end_clean();
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
                
                ob_end_clean();
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
            
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'transactions' => $transactions
            ]);
            break;
        
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    ob_end_clean(); // Clear buffer before error response
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
        // Get doctor_id - first try from payment_transactions record, then fallback to related table
        $doctorId = $transaction['doctor_id'];
        
        if (!$doctorId) {
            // Determine table name based on related_type
            $tableName = ($transaction['related_type'] === 'appointment') ? 'appointments' : 'consultations';
            
            error_log("REVENUE SPLIT: Fetching doctor_id from $tableName for related_id " . $transaction['related_id']);
            
            $result = $conn->query("
                SELECT doctor_id FROM $tableName WHERE id = {$transaction['related_id']}
            ");
            $record = $result ? $result->fetch_assoc() : null;
            $doctorId = $record ? $record['doctor_id'] : null;
        }
        
        if ($doctorId && $transaction['related_id']) {
            // CRITICAL: Verify consultation exists before inserting earnings
            // This prevents foreign key constraint error
            $consultationCheck = $conn->query("
                SELECT id FROM consultations WHERE id = {$transaction['related_id']}
            ");
            
            if ($consultationCheck && $consultationCheck->num_rows > 0) {
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
                    $doctorId, $transaction['related_id'],
                    $transaction['amount'], $commissionPercent,
                    $platformCommission, $providerAmount
                );
                $stmt->execute();
            } else {
                // Consultation doesn't exist - log warning but don't fail
                error_log("Payment verification warning: Consultation ID {$transaction['related_id']} not found, skipping earnings insert");
            }
        } else {
            error_log("REVENUE SPLIT ERROR: Could not determine doctor_id for transaction " . $transaction['id']);
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