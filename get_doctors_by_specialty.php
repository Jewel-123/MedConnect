<?php
/**
 * Get Doctors by Specialty API
 * Fetches approved doctors matching a specialty for patient selection
 */

session_start();
header('Content-Type: application/json');
require_once 'db.php';

// Allow patients to access this
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$specialty = $_GET['specialty'] ?? '';

if (empty($specialty)) {
    echo json_encode(['success' => false, 'error' => 'Specialty required']);
    exit;
}

try {
    // Fetch approved doctors with matching specialization
    $stmt = $conn->prepare("
        SELECT u.id, u.full_name, dp.specialization, dp.consultation_fee as fee, dp.languages_spoken,
               COALESCE(AVG(r.rating), 0) as rating, COUNT(r.id) as review_count
        FROM users u
        INNER JOIN doctor_profiles dp ON u.id = dp.user_id
        LEFT JOIN doctor_reviews r ON u.id = r.doctor_id
        WHERE u.role = 'doctor' 
          AND u.status = 'approved'
          AND dp.specialization = ?
        GROUP BY u.id
        ORDER BY rating DESC, review_count DESC
        LIMIT 10
    ");
    
    $stmt->bind_param("s", $specialty);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $doctors = [];
    while ($row = $result->fetch_assoc()) {
        $doctors[] = [
            'id' => $row['id'],
            'full_name' => $row['full_name'],
            'specialization' => $row['specialization'],
            'fee' => floatval($row['fee']),
            'rating' => number_format($row['rating'], 1),
            'review_count' => $row['review_count'],
            'languages' => $row['languages_spoken']
        ];
    }
    
    // If no exact specialty match, try to get any available approved doctor
    if (empty($doctors)) {
        $result = $conn->query("
            SELECT u.id, u.full_name, dp.specialization, dp.consultation_fee as fee,
                   COALESCE(AVG(r.rating), 0) as rating, COUNT(r.id) as review_count
            FROM users u
            INNER JOIN doctor_profiles dp ON u.id = dp.user_id
            LEFT JOIN doctor_reviews r ON u.id = r.doctor_id
            WHERE u.role = 'doctor' AND u.status = 'approved'
            GROUP BY u.id
            ORDER BY rating DESC
            LIMIT 5
        ");
        
        while ($row = $result->fetch_assoc()) {
            $doctors[] = [
                'id' => $row['id'],
                'full_name' => $row['full_name'],
                'specialization' => $row['specialization'],
                'fee' => floatval($row['fee']),
                'rating' => number_format($row['rating'], 1),
                'review_count' => $row['review_count']
            ];
        }
    }
    
    echo json_encode(['success' => true, 'doctors' => $doctors]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
