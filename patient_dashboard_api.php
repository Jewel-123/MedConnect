<?php
/**
 * Patient Dashboard API
 * Provides prescription data for patient dashboard
 */

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'db.php';

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$action = $_GET['action'] ?? '';
$patientId = $_SESSION['user_id'];

try {
    switch ($action) {
        
        // ==================================================
        // Get Complete Dashboard Data
        // ==================================================
        case 'get_dashboard':
            // Active Prescriptions (finalized, sent_to_pharmacy, in_progress, ready)
            $activePrescriptions = $conn->query("
                SELECT p.*, 
                       c.symptoms,
                       u.full_name as doctor_name,
                       dp.specialization,
                       pharm.full_name as pharmacy_name,
                       pharm.phone as pharmacy_phone
                FROM prescriptions_v2 p
                JOIN consultations c ON p.consultation_id = c.id
                JOIN users u ON p.doctor_id = u.id
                LEFT JOIN doctor_profiles dp ON u.id = dp.user_id
                LEFT JOIN users pharm ON p.pharmacy_id = pharm.id
                WHERE p.patient_id = $patientId
                AND p.status IN ('finalized', 'sent_to_pharmacy', 'in_progress', 'ready')
                ORDER BY p.created_at DESC
            ")->fetch_all(MYSQLI_ASSOC);
            
            // Get items for each active prescription
            foreach ($activePrescriptions as &$rx) {
                $rx['items'] = $conn->query("
                    SELECT * FROM prescription_items_v2
                    WHERE prescription_id = {$rx['id']}
                ")->fetch_all(MYSQLI_ASSOC);
            }
            
            // Active Orders (with order details)
            $activeOrders = $conn->query("
                SELECT p.*, 
                       po.order_number,
                       po.order_status,
                       po.total_amount,
                       po.fulfillment_type,
                       pharm.full_name as pharmacy_name,
                       pharm.phone as pharmacy_phone
                FROM prescriptions_v2 p
                JOIN prescription_orders po ON p.id = po.prescription_id
                LEFT JOIN users pharm ON po.pharmacy_id = pharm.id
                WHERE p.patient_id = $patientId
                AND p.status IN ('sent_to_pharmacy', 'in_progress', 'ready')
                ORDER BY p.updated_at DESC
            ")->fetch_all(MYSQLI_ASSOC);
            
            // Past Prescriptions (completed, cancelled)
            $pastPrescriptions = $conn->query("
                SELECT p.*, 
                       c.symptoms,
                       u.full_name as doctor_name,
                       dp.specialization,
                       po.order_number
                FROM prescriptions_v2 p
                JOIN consultations c ON p.consultation_id = c.id
                JOIN users u ON p.doctor_id = u.id
                LEFT JOIN doctor_profiles dp ON u.id = dp.user_id
                LEFT JOIN prescription_orders po ON p.id = po.prescription_id
                WHERE p.patient_id = $patientId
                AND p.status IN ('completed', 'cancelled')
                ORDER BY p.updated_at DESC
                LIMIT 20
            ")->fetch_all(MYSQLI_ASSOC);
            
            // Get items for past prescriptions
            foreach ($pastPrescriptions as &$rx) {
                $rx['items'] = $conn->query("
                    SELECT * FROM prescription_items_v2
                    WHERE prescription_id = {$rx['id']}
                ")->fetch_all(MYSQLI_ASSOC);
            }
            
            // Statistics
            $stats = $conn->query("
                SELECT 
                    COUNT(*) as total_prescriptions,
                    SUM(CASE WHEN status IN ('finalized', 'sent_to_pharmacy', 'in_progress', 'ready') THEN 1 ELSE 0 END) as active_count,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count
                FROM prescriptions_v2
                WHERE patient_id = $patientId
                AND status != 'draft'
            ")->fetch_assoc();
            
            echo json_encode([
                'success' => true,
                'active_prescriptions' => $activePrescriptions,
                'active_orders' => $activeOrders,
                'past_prescriptions' => $pastPrescriptions,
                'stats' => [
                    'total_prescriptions' => intval($stats['total_prescriptions']),
                    'active_count' => intval($stats['active_count']),
                    'completed_count' => intval($stats['completed_count']),
                    'cancelled_count' => intval($stats['cancelled_count'])
                ]
            ]);
            break;
        
        // ==================================================
        // Get Active Prescriptions Only
        // ==================================================
        case 'get_active_prescriptions':
            $prescriptions = $conn->query("
                SELECT p.*, 
                       c.symptoms,
                       u.full_name as doctor_name,
                       dp.specialization,
                       pharm.full_name as pharmacy_name
                FROM prescriptions_v2 p
                JOIN consultations c ON p.consultation_id = c.id
                JOIN users u ON p.doctor_id = u.id
                LEFT JOIN doctor_profiles dp ON u.id = dp.user_id
                LEFT JOIN users pharm ON p.pharmacy_id = pharm.id
                WHERE p.patient_id = $patientId
                AND p.status IN ('finalized', 'sent_to_pharmacy', 'in_progress', 'ready')
                ORDER BY p.created_at DESC
            ")->fetch_all(MYSQLI_ASSOC);
            
            foreach ($prescriptions as &$rx) {
                $rx['items'] = $conn->query("
                    SELECT * FROM prescription_items_v2
                    WHERE prescription_id = {$rx['id']}
                ")->fetch_all(MYSQLI_ASSOC);
            }
            
            echo json_encode([
                'success' => true,
                'prescriptions' => $prescriptions
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
