<?php
/**
 * Patient API - Post-Consultation Workflow
 * Handles patient-facing actions after consultation completion
 */

// Disable display errors to prevent JSON corruption
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'patient_api_errors.log');

session_start();
require_once 'db.php';

header('Content-Type: application/json');

// Custom logger for debugging
function api_log($message) {
    file_put_contents('patient_api_debug.log', date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$patient_id = $_SESSION['user_id'];

// Robust action handling
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $_POST['action'] ?? $input['action'] ?? '';

try {
    switch ($action) {
        
        // ========================================
        // GET CONSULTATION SUMMARY
        // ========================================
        case 'get_consultation_summary':
            $consultation_id = $_GET['consultation_id'] ?? 0;
            
            if (!$consultation_id) {
                throw new Exception('Consultation ID is required');
            }
            
            // Get consultation details with doctor info
            $stmt = $conn->prepare("
                SELECT 
                    c.*,
                    u.full_name as doctor_name,
                    u.email as doctor_email,
                    dp.specialization,
                    dp.qualification,
                    dp.photo_url as doctor_photo
                FROM consultations c
                LEFT JOIN users u ON c.doctor_id = u.id
                LEFT JOIN doctor_profiles dp ON u.id = dp.user_id
                WHERE c.id = ? AND c.patient_id = ?
            ");
            $stmt->bind_param("ii", $consultation_id, $patient_id);
            $stmt->execute();
            $consultation = $stmt->get_result()->fetch_assoc();
            
            if (!$consultation) {
                throw new Exception('Consultation not found');
            }
            
            // Get prescription if exists
            $stmt = $conn->prepare("
                SELECT * FROM prescriptions_v2 
                WHERE consultation_id = ? AND patient_id = ?
            ");
            $stmt->bind_param("ii", $consultation_id, $patient_id);
            $stmt->execute();
            $prescription = $stmt->get_result()->fetch_assoc();
            
            // Get prescription items if prescription exists
            $prescription_items = [];
            if ($prescription) {
                $stmt = $conn->prepare("
                    SELECT * FROM prescription_items_v2 
                    WHERE prescription_id = ?
                ");
                $stmt->bind_param("i", $prescription['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $prescription_items[] = $row;
                }
            }
            
            echo json_encode([
                'status' => 'success',
                'consultation' => $consultation,
                'prescription' => $prescription,
                'prescription_items' => $prescription_items
            ]);
            break;
            
        // ========================================
        // GET ACTIVE PRESCRIPTIONS
        // ========================================
        case 'get_active_prescriptions':
            $stmt = $conn->prepare("
                SELECT 
                    p.*,
                    u.full_name as doctor_name,
                    c.created_at as consultation_date
                FROM prescriptions_v2 p
                LEFT JOIN users u ON p.doctor_id = u.id
                LEFT JOIN consultations c ON p.consultation_id = c.id
                WHERE p.patient_id = ? 
                    AND p.status IN ('issued', 'sent_to_pharmacy')
                ORDER BY p.created_at DESC
                LIMIT 10
            ");
            $stmt->bind_param("i", $patient_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $prescriptions = [];
            while ($row = $result->fetch_assoc()) {
                $prescriptions[] = $row;
            }
            
            echo json_encode([
                'status' => 'success',
                'prescriptions' => $prescriptions,
                'count' => count($prescriptions)
            ]);
            break;
            
        // ========================================
        // GET PRESCRIPTION DETAILS
        // ========================================
        case 'get_prescription_details':
            $prescription_id = $_GET['prescription_id'] ?? 0;
            
            if (!$prescription_id) {
                throw new Exception('Prescription ID is required');
            }
            
            // Get prescription
            $stmt = $conn->prepare("
                SELECT 
                    p.*,
                    u.full_name as doctor_name,
                    dp.qualification,
                    dp.registration_number
                FROM prescriptions_v2 p
                LEFT JOIN users u ON p.doctor_id = u.id
                LEFT JOIN doctor_profiles dp ON u.id = dp.user_id
                WHERE p.id = ? AND p.patient_id = ?
            ");
            $stmt->bind_param("ii", $prescription_id, $patient_id);
            $stmt->execute();
            $prescription = $stmt->get_result()->fetch_assoc();
            
            if (!$prescription) {
                throw new Exception('Prescription not found');
            }
            
            // Get prescription items
            $stmt = $conn->prepare("
                SELECT * FROM prescription_items_v2 
                WHERE prescription_id = ?
            ");
            $stmt->bind_param("i", $prescription_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $items = [];
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
            
            echo json_encode([
                'status' => 'success',
                'prescription' => $prescription,
                'items' => $items
            ]);
            break;
            
        // ========================================
        // CREATE MEDICINE ORDER
        // ========================================
        case 'create_medicine_order':
            if (!$input) {
                throw new Exception('Invalid JSON input');
            }
            
            $prescription_id = $input['prescription_id'] ?? 0;
            $fulfillment_type = $input['fulfillment_type'] ?? 'pickup'; // pickup or delivery
            $delivery_address = $input['delivery_address'] ?? null;
            $delivery_contact = $input['delivery_contact'] ?? null;
            
            if (!$prescription_id) {
                throw new Exception('Prescription ID is required');
            }
            
            // Verify prescription belongs to patient
            $stmt = $conn->prepare("
                SELECT * FROM prescriptions_v2 
                WHERE id = ? AND patient_id = ?
            ");
            $stmt->bind_param("ii", $prescription_id, $patient_id);
            $stmt->execute();
            $prescription = $stmt->get_result()->fetch_assoc();
            
            if (!$prescription) {
                throw new Exception('Prescription not found');
            }
            
            // Get pharmacy (use assigned pharmacy or find nearest)
            $pharmacy_id = $prescription['pharmacy_id'];
            
            if (!$pharmacy_id) {
                // Find first available pharmacy
                $result = $conn->query("
                    SELECT id FROM users 
                    WHERE role = 'pharmacy' AND status = 'approved' 
                    LIMIT 1
                ");
                $pharmacy = $result->fetch_assoc();
                $pharmacy_id = $pharmacy['id'] ?? null;
                
                if (!$pharmacy_id) {
                    throw new Exception('No pharmacy available');
                }
            }
            
            // Calculate total (simplified - in production, fetch actual prices)
            $total_amount = 100.00; // Placeholder
            
            // Generate order number
            $order_number = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Create order
            $stmt = $conn->prepare("
                INSERT INTO prescription_orders 
                (order_number, prescription_id, pharmacy_id, patient_id, order_status, 
                 fulfillment_type, delivery_address, delivery_contact, total_amount, payment_status)
                VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, ?, 'pending')
            ");
            $stmt->bind_param("siiisssd", 
                $order_number, 
                $prescription_id, 
                $pharmacy_id, 
                $patient_id,
                $fulfillment_type,
                $delivery_address,
                $delivery_contact,
                $total_amount
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to create order: ' . $stmt->error);
            }
            
            $order_id = $stmt->insert_id;
            
            // Send notification to pharmacy
            require_once 'notification_service.php';
            $notificationService = new NotificationService($conn);
            $notificationService->send(
                $pharmacy_id,
                'all',
                'New Prescription Order',
                "New order #$order_number received from patient.",
                ['notification_type' => 'new_order', 'related_id' => $order_id]
            );
            
            echo json_encode([
                'status' => 'success',
                'order_id' => $order_id,
                'order_number' => $order_number,
                'message' => 'Order created successfully'
            ]);
            break;
            
        // ========================================
        // SUBMIT FEEDBACK
        // ========================================
        case 'submit_feedback':
            api_log("Submit feedback called. Input: " . print_r($input, true));
            if (!$input) {
                api_log("Invalid JSON input");
                throw new Exception('Invalid JSON input');
            }
            
            $consultation_id = $input['consultation_id'] ?? 0;
            $doctor_id = $input['doctor_id'] ?? 0;
            $rating = $input['rating'] ?? 0;
            $review_text = $input['review_text'] ?? '';
            
            if (!$consultation_id || !$doctor_id || !$rating) {
                api_log("Missing required fields: Consult=$consultation_id, Doc=$doctor_id, Rating=$rating");
                throw new Exception('Consultation ID, Doctor ID, and rating are required');
            }
            
            if ($rating < 1 || $rating > 5) {
                throw new Exception('Rating must be between 1 and 5');
            }
            
            // Check if feedback already exists
            $stmt = $conn->prepare("
                SELECT id FROM doctor_reviews 
                WHERE consultation_id = ? AND patient_id = ?
            ");
            $stmt->bind_param("ii", $consultation_id, $patient_id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            
            if ($existing) {
                throw new Exception('Feedback already submitted for this consultation');
            }
            
            // Insert review
            $stmt = $conn->prepare("
                INSERT INTO doctor_reviews 
                (doctor_id, patient_id, consultation_id, rating, review_text)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iiiis", $doctor_id, $patient_id, $consultation_id, $rating, $review_text);
            
            if (!$stmt->execute()) {
                api_log("DB Error during insert: " . $stmt->error);
                throw new Exception('Failed to submit feedback: ' . $stmt->error);
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Feedback submitted successfully'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    api_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();