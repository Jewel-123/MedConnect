&lt;?php
/**
 * Patient API - Post-Consultation Workflow
 * Handles patient-facing actions after consultation completion
 */

session_start();
require_once 'db.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    http_response_code(401);
    echo json_encode(['status' =&gt; 'error', 'message' =&gt; 'Unauthorized']);
    exit;
}

$patient_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

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
            $stmt = $conn-&gt;prepare("
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
            $stmt-&gt;bind_param("ii", $consultation_id, $patient_id);
            $stmt-&gt;execute();
            $consultation = $stmt-&gt;get_result()-&gt;fetch_assoc();
            
            if (!$consultation) {
                throw new Exception('Consultation not found');
            }
            
            // Get prescription if exists
            $stmt = $conn-&gt;prepare("
                SELECT * FROM prescriptions_v2 
                WHERE consultation_id = ? AND patient_id = ?
            ");
            $stmt-&gt;bind_param("ii", $consultation_id, $patient_id);
            $stmt-&gt;execute();
            $prescription = $stmt-&gt;get_result()-&gt;fetch_assoc();
            
            // Get prescription items if prescription exists
            $prescription_items = [];
            if ($prescription) {
                $stmt = $conn-&gt;prepare("
                    SELECT * FROM prescription_items_v2 
                    WHERE prescription_id = ?
                ");
                $stmt-&gt;bind_param("i", $prescription['id']);
                $stmt-&gt;execute();
                $result = $stmt-&gt;get_result();
                while ($row = $result-&gt;fetch_assoc()) {
                    $prescription_items[] = $row;
                }
            }
            
            echo json_encode([
                'status' =&gt; 'success',
                'consultation' =&gt; $consultation,
                'prescription' =&gt; $prescription,
                'prescription_items' =&gt; $prescription_items
            ]);
            break;
            
        // ========================================
        // GET ACTIVE PRESCRIPTIONS
        // ========================================
        case 'get_active_prescriptions':
            $stmt = $conn-&gt;prepare("
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
            $stmt-&gt;bind_param("i", $patient_id);
            $stmt-&gt;execute();
            $result = $stmt-&gt;get_result();
            
            $prescriptions = [];
            while ($row = $result-&gt;fetch_assoc()) {
                $prescriptions[] = $row;
            }
            
            echo json_encode([
                'status' =&gt; 'success',
                'prescriptions' =&gt; $prescriptions,
                'count' =&gt; count($prescriptions)
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
            $stmt = $conn-&gt;prepare("
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
            $stmt-&gt;bind_param("ii", $prescription_id, $patient_id);
            $stmt-&gt;execute();
            $prescription = $stmt-&gt;get_result()-&gt;fetch_assoc();
            
            if (!$prescription) {
                throw new Exception('Prescription not found');
            }
            
            // Get prescription items
            $stmt = $conn-&gt;prepare("
                SELECT * FROM prescription_items_v2 
                WHERE prescription_id = ?
            ");
            $stmt-&gt;bind_param("i", $prescription_id);
            $stmt-&gt;execute();
            $result = $stmt-&gt;get_result();
            
            $items = [];
            while ($row = $result-&gt;fetch_assoc()) {
                $items[] = $row;
            }
            
            echo json_encode([
                'status' =&gt; 'success',
                'prescription' =&gt; $prescription,
                'items' =&gt; $items
            ]);
            break;
            
        // ========================================
        // CREATE MEDICINE ORDER
        // ========================================
        case 'create_medicine_order':
            $input = json_decode(file_get_contents('php://input'), true);
            
            $prescription_id = $input['prescription_id'] ?? 0;
            $fulfillment_type = $input['fulfillment_type'] ?? 'pickup'; // pickup or delivery
            $delivery_address = $input['delivery_address'] ?? null;
            $delivery_contact = $input['delivery_contact'] ?? null;
            
            if (!$prescription_id) {
                throw new Exception('Prescription ID is required');
            }
            
            // Verify prescription belongs to patient
            $stmt = $conn-&gt;prepare("
                SELECT * FROM prescriptions_v2 
                WHERE id = ? AND patient_id = ?
            ");
            $stmt-&gt;bind_param("ii", $prescription_id, $patient_id);
            $stmt-&gt;execute();
            $prescription = $stmt-&gt;get_result()-&gt;fetch_assoc();
            
            if (!$prescription) {
                throw new Exception('Prescription not found');
            }
            
            // Get pharmacy (use assigned pharmacy or find nearest)
            $pharmacy_id = $prescription['pharmacy_id'];
            
            if (!$pharmacy_id) {
                // Find first available pharmacy
                $result = $conn-&gt;query("
                    SELECT id FROM users 
                    WHERE role = 'pharmacy' AND status = 'approved' 
                    LIMIT 1
                ");
                $pharmacy = $result-&gt;fetch_assoc();
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
            $stmt = $conn-&gt;prepare("
                INSERT INTO prescription_orders 
                (order_number, prescription_id, pharmacy_id, patient_id, order_status, 
                 fulfillment_type, delivery_address, delivery_contact, total_amount, payment_status)
                VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, ?, 'pending')
            ");
            $stmt-&gt;bind_param("siiisssd", 
                $order_number, 
                $prescription_id, 
                $pharmacy_id, 
                $patient_id,
                $fulfillment_type,
                $delivery_address,
                $delivery_contact,
                $total_amount
            );
            
            if (!$stmt-&gt;execute()) {
                throw new Exception('Failed to create order: ' . $stmt-&gt;error);
            }
            
            $order_id = $stmt-&gt;insert_id;
            
            // Send notification to pharmacy
            require_once 'notification_service.php';
            $notificationService = new NotificationService($conn);
            $notificationService-&gt;send(
                $pharmacy_id,
                'all',
                'New Prescription Order',
                "New order #$order_number received from patient.",
                ['notification_type' =&gt; 'new_order', 'related_id' =&gt; $order_id]
            );
            
            echo json_encode([
                'status' =&gt; 'success',
                'order_id' =&gt; $order_id,
                'order_number' =&gt; $order_number,
                'message' =&gt; 'Order created successfully'
            ]);
            break;
            
        // ========================================
        // SUBMIT FEEDBACK
        // ========================================
        case 'submit_feedback':
            $input = json_decode(file_get_contents('php://input'), true);
            
            $consultation_id = $input['consultation_id'] ?? 0;
            $doctor_id = $input['doctor_id'] ?? 0;
            $rating = $input['rating'] ?? 0;
            $review_text = $input['review_text'] ?? '';
            
            if (!$consultation_id || !$doctor_id || !$rating) {
                throw new Exception('Consultation ID, Doctor ID, and rating are required');
            }
            
            if ($rating &lt; 1 || $rating &gt; 5) {
                throw new Exception('Rating must be between 1 and 5');
            }
            
            // Check if feedback already exists
            $stmt = $conn-&gt;prepare("
                SELECT id FROM doctor_reviews 
                WHERE consultation_id = ? AND patient_id = ?
            ");
            $stmt-&gt;bind_param("ii", $consultation_id, $patient_id);
            $stmt-&gt;execute();
            $existing = $stmt-&gt;get_result()-&gt;fetch_assoc();
            
            if ($existing) {
                throw new Exception('Feedback already submitted for this consultation');
            }
            
            // Insert review
            $stmt = $conn-&gt;prepare("
                INSERT INTO doctor_reviews 
                (doctor_id, patient_id, consultation_id, rating, review_text)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt-&gt;bind_param("iiiis", $doctor_id, $patient_id, $consultation_id, $rating, $review_text);
            
            if (!$stmt-&gt;execute()) {
                throw new Exception('Failed to submit feedback: ' . $stmt-&gt;error);
            }
            
            echo json_encode([
                'status' =&gt; 'success',
                'message' =&gt; 'Feedback submitted successfully'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' =&gt; 'error',
        'message' =&gt; $e-&gt;getMessage()
    ]);
}

$conn-&gt;close();
?&gt;
