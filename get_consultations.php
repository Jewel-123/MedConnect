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
            id,
            symptoms,
            duration,
            severity,
            age,
            gender,
            existing_conditions,
            input_method,
            status,
            created_at,
            updated_at
        FROM consultations 
        WHERE patient_id = ?
        ORDER BY created_at DESC
    ");
    
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $patientId);
    
    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $consultations = [];
    
    while ($row = $result->fetch_assoc()) {
        // Format the data
        $consultations[] = [
            'id' => $row['id'],
            'symptoms' => $row['symptoms'],
            'symptoms_preview' => strlen($row['symptoms']) > 100 
                ? substr($row['symptoms'], 0, 100) . '...' 
                : $row['symptoms'],
            'duration' => $row['duration'],
            'severity' => $row['severity'],
            'age' => $row['age'],
            'gender' => $row['gender'],
            'existing_conditions' => $row['existing_conditions'],
            'input_method' => $row['input_method'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'created_at_formatted' => date('M d, Y g:i A', strtotime($row['created_at']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'count' => count($consultations),
        'consultations' => $consultations
    ], JSON_PRETTY_PRINT);
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
