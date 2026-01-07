<?php
/**
 * Start Consultation API
 * Handles patient consultation form submissions
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
        'error' => 'Only patients can start consultations.'
    ]);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['symptoms', 'duration', 'severity'];
    $missing = [];
    
    foreach ($required as $field) {
        if (empty($input[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing required fields: ' . implode(', ', $missing)
        ]);
        exit;
    }
    
    // Validate severity value
    $validSeverity = ['low', 'medium', 'high'];
    if (!in_array($input['severity'], $validSeverity)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid severity value. Must be: low, medium, or high.'
        ]);
        exit;
    }
    
    // Validate gender if provided
    if (!empty($input['gender'])) {
        $validGender = ['male', 'female', 'other'];
        if (!in_array($input['gender'], $validGender)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid gender value. Must be: male, female, or other.'
            ]);
            exit;
        }
    }
    
    // Validate input method
    $inputMethod = isset($input['input_method']) ? $input['input_method'] : 'text';
    $validInputMethods = ['text', 'voice'];
    if (!in_array($inputMethod, $validInputMethods)) {
        $inputMethod = 'text';
    }
    
    // Sanitize inputs
    $patientId = $_SESSION['user_id'];
    $symptoms = trim($input['symptoms']);
    $duration = trim($input['duration']);
    $severity = $input['severity'];
    $age = !empty($input['age']) ? intval($input['age']) : null;
    $gender = !empty($input['gender']) ? $input['gender'] : null;
    $existingConditions = !empty($input['existing_conditions']) ? trim($input['existing_conditions']) : null;
    
    // Prepare SQL statement
    $stmt = $conn->prepare("
        INSERT INTO consultations 
        (patient_id, symptoms, duration, severity, age, gender, existing_conditions, input_method, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param(
        "isssisss",
        $patientId,
        $symptoms,
        $duration,
        $severity,
        $age,
        $gender,
        $existingConditions,
        $inputMethod
    );
    
    if ($stmt->execute()) {
        $consultationId = $stmt->insert_id;
        
        echo json_encode([
            'success' => true,
            'message' => 'Consultation started successfully!',
            'consultation_id' => $consultationId,
            'data' => [
                'id' => $consultationId,
                'symptoms' => $symptoms,
                'duration' => $duration,
                'severity' => $severity,
                'status' => 'pending',
                'input_method' => $inputMethod
            ]
        ], JSON_PRETTY_PRINT);
    } else {
        throw new Exception("Failed to create consultation: " . $stmt->error);
    }
    
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
