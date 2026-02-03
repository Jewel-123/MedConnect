<?php
/**
 * Prescription Review API
 * Handles review submission for completed prescription orders
 */

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'db.php';

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Patient access required.']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$patientId = $_SESSION['user_id'];

try {
    switch ($action) {
        
        // ==================================================
        // Submit Review
        // ==================================================
        case 'submit_review':
            $prescriptionId = intval($_POST['prescription_id'] ?? 0);
            $orderId = intval($_POST['prescription_order_id'] ?? 0);
            $pharmacyId = intval($_POST['pharmacy_id'] ?? 0);
            $rating = intval($_POST['rating'] ?? 0);
            $serviceQuality = !empty($_POST['service_quality']) ? intval($_POST['service_quality']) : null;
            $deliverySpeed = !empty($_POST['delivery_speed']) ? intval($_POST['delivery_speed']) : null;
            $medicineQuality = !empty($_POST['medicine_quality']) ? intval($_POST['medicine_quality']) : null;
            $reviewText = trim($_POST['review_text'] ?? '');
            $wouldRecommend = filter_var($_POST['would_recommend'] ?? true, FILTER_VALIDATE_BOOLEAN);
            
            if (!$prescriptionId || !$orderId || !$pharmacyId || $rating < 1 || $rating > 5) {
                throw new Exception('Invalid review data. Rating must be between 1 and 5.');
            }
            
            // Verify prescription order belongs to patient and is completed
            $stmt = $conn->prepare("
                SELECT id FROM prescription_orders 
                WHERE id = ? AND prescription_id = ? AND patient_id = ? AND order_status = 'completed'
            ");
            $stmt->bind_param("iii", $orderId, $prescriptionId, $patientId);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception('Prescription order not found or not completed');
            }
            
            // Check if review already exists
            $stmt = $conn->prepare("
                SELECT id FROM prescription_reviews 
                WHERE prescription_id = ? AND patient_id = ?
            ");
            $stmt->bind_param("ii", $prescriptionId, $patientId);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception('You have already submitted a review for this prescription');
            }
            
            // Insert review
            $stmt = $conn->prepare("
                INSERT INTO prescription_reviews (
                    prescription_id, prescription_order_id, patient_id, pharmacy_id,
                    rating, service_quality, delivery_speed, medicine_quality,
                    review_text, would_recommend, is_verified_purchase, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE, NOW())
            ");
            $stmt->bind_param("iiiiiiiisi", 
                $prescriptionId, $orderId, $patientId, $pharmacyId,
                $rating, $serviceQuality, $deliverySpeed, $medicineQuality,
                $reviewText, $wouldRecommend
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to submit review');
            }
            
            // Update prescription_orders to mark review as submitted
            $conn->query("
                UPDATE prescription_orders 
                SET review_submitted = TRUE 
                WHERE id = $orderId
            ");
            
            echo json_encode([
                'success' => true,
                'message' => 'Review submitted successfully',
                'review_id' => $conn->insert_id
            ]);
            break;
        
        // ==================================================
        // Get Reviews for Pharmacy
        // ==================================================
        case 'get_pharmacy_reviews':
            $pharmacyId = intval($_GET['pharmacy_id'] ?? 0);
            $limit = intval($_GET['limit'] ?? 10);
            
            if (!$pharmacyId) {
                throw new Exception('Pharmacy ID is required');
            }
            
            // Get reviews
            $reviews = $conn->query("
                SELECT r.*, u.full_name as patient_name,
                       po.order_number
                FROM prescription_reviews r
                JOIN users u ON r.patient_id = u.id
                JOIN prescription_orders po ON r.prescription_order_id = po.id
                WHERE r.pharmacy_id = $pharmacyId AND r.is_published = TRUE
                ORDER BY r.created_at DESC
                LIMIT $limit
            ")->fetch_all(MYSQLI_ASSOC);
            
            // Get average ratings
            $stats = $conn->query("
                SELECT 
                    COUNT(*) as total_reviews,
                    AVG(rating) as avg_rating,
                    AVG(service_quality) as avg_service,
                    AVG(delivery_speed) as avg_delivery,
                    AVG(medicine_quality) as avg_medicine,
                    SUM(CASE WHEN would_recommend = TRUE THEN 1 ELSE 0 END) as recommend_count
                FROM prescription_reviews
                WHERE pharmacy_id = $pharmacyId AND is_published = TRUE
            ")->fetch_assoc();
            
            echo json_encode([
                'success' => true,
                'reviews' => $reviews,
                'stats' => [
                    'total_reviews' => intval($stats['total_reviews']),
                    'avg_rating' => round(floatval($stats['avg_rating']), 1),
                    'avg_service' => round(floatval($stats['avg_service']), 1),
                    'avg_delivery' => round(floatval($stats['avg_delivery']), 1),
                    'avg_medicine' => round(floatval($stats['avg_medicine']), 1),
                    'recommend_percentage' => $stats['total_reviews'] > 0 
                        ? round(($stats['recommend_count'] / $stats['total_reviews']) * 100, 1)
                        : 0
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
?>
