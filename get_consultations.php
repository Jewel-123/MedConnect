<?php
/**
 * Get Consultations API
 * Retrieves consultation history for logged-in patient
 */

session_start();
require_once 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized. Please log in.'
    ]);
    exit;
}

// Check if user is a patient
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Only patients can view consultations.'
    ]);
    exit;
}

try {
    $patientId = $_SESSION['user_id'];
    
    // Prepare SQL statement to get consultations
    $stmt = $conn->prepare("
        SELECT 
            c.*,
            u.full_name as doctor_name,
            'consultation' as type,
            dr.id as review_id
        FROM consultations c
        LEFT JOIN users u ON c.doctor_id = u.id
        LEFT JOIN doctor_reviews dr ON c.id = dr.consultation_id
        WHERE c.patient_id = ?
        ORDER BY c.created_at DESC
    ");
    
    $stmt->bind_param("i", $patientId);
    $stmt->execute();
    $result = $stmt->get_result();
    $activity = [];
    
    while ($row = $result->fetch_assoc()) {
        $activity[] = [
            'id' => $row['id'],
            'type' => 'consultation',
            'symptoms_preview' => strlen($row['symptoms']) > 100 
                ? substr($row['symptoms'], 0, 100) . '...' 
                : $row['symptoms'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'created_at_formatted' => date('M d, Y g:i A', strtotime($row['created_at'])),
            'doctor_name' => ($row['status'] === 'pending' || $row['status'] === 'assigned' || !$row['doctor_id']) ? 'Seeking Doctor' : $row['doctor_name'],
            'doctor_id' => $row['doctor_id'],
            'review_id' => $row['review_id']
        ];
    }
    
    // Also fetch appointments
    $apptStmt = $conn->prepare("
        SELECT 
            a.*,
            u.full_name as doctor_name,
            'appointment' as type
        FROM appointments a
        LEFT JOIN users u ON a.doctor_id = u.id
        WHERE a.patient_id = ?
        AND a.id NOT IN (SELECT appointment_id FROM consultations WHERE appointment_id IS NOT NULL)
        ORDER BY a.created_at DESC
    ");
    $apptStmt->bind_param("i", $patientId);
    $apptStmt->execute();
    $apptRes = $apptStmt->get_result();
    
    while ($row = $apptRes->fetch_assoc()) {
        $activity[] = [
            'id' => $row['id'],
            'type' => 'appointment',
            'symptoms_preview' => $row['notes'] ? $row['notes'] : 'General Checkup',
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'created_at_formatted' => date('M d, Y g:i A', strtotime($row['created_at'])),
            'doctor_name' => ($row['status'] === 'pending' || $row['status'] === 'booked' || !$row['doctor_id']) ? 'Seeking Doctor' : $row['doctor_name']
        ];
    }
    
    // Sort combined activity by created_at DESC
    usort($activity, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    echo json_encode([
        'success' => true,
        'count' => count($activity),
        'consultations' => $activity // Keep key name for frontend compatibility
    ], JSON_PRETTY_PRINT);
    
    $stmt->close();
    $apptStmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

// Connection closed automatically by PHP at end of request