<?php
/**
 * Start Consultation API
 * Handles patient consultation form submissions
 */

error_reporting(0);
ini_set('display_errors', 0);
ob_start();
session_start();
require_once 'db.php';

function debug_log($msg) {
    file_put_contents('consultation_debug.log', date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
}

debug_log("Starting consultation submission request");
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized. Please log in.'
    ]);
    exit;
}

// Check if user is a patient
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    http_response_code(403);
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Only patients can start consultations.'
    ]);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    ob_clean();
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
        ob_clean();
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
        ob_clean();
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
            ob_clean();
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
    
    // Consultation Mode
    $consultationMode = isset($input['consultation_mode']) ? $input['consultation_mode'] : 'text';
    $validModes = ['text', 'audio', 'video'];
    if (!in_array($consultationMode, $validModes)) {
        $consultationMode = 'text';
    }
    
    // Attachment Handling
    $attachmentPath = null;
    if (!empty($input['attachment_base64']) && !empty($input['attachment_name'])) {
        $uploadDir = 'uploads/reports/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $input['attachment_name']);
        $filePath = $uploadDir . $fileName;
        $fileData = base64_decode($input['attachment_base64']);
        if (file_put_contents($filePath, $fileData)) {
            $attachmentPath = $filePath;
        }
    }
    
    // Sanitize inputs
    $patientId = $_SESSION['user_id'];
    $symptoms = trim($input['symptoms']);
    $duration = trim($input['duration']);
    $severity = $input['severity'];
    $age = !empty($input['age']) ? intval($input['age']) : null;
    $gender = !empty($input['gender']) ? $input['gender'] : null;
    $existingConditions = !empty($input['existing_conditions']) ? trim($input['existing_conditions']) : null;
    
    // ========================================
    // NLP SYMPTOM ANALYSIS
    // ========================================
    debug_log("Running NLP analysis on symptoms: " . substr($symptoms, 0, 50));
    require_once 'nlp_symptom_analyzer.php';
    $analyzer = new SymptomAnalyzer($conn);
    $analysis = $analyzer->analyze($symptoms);
    
    $urgencyScore = $analysis['urgency_score'];
    $urgencyLevel = $analysis['urgency_level'];
    $matchedSpecialty = $analysis['primary_specialty'];
    $isEmergency = $analysis['is_emergency'];
    debug_log("NLP Result: Specialty=$matchedSpecialty, Urgency=$urgencyLevel ($urgencyScore)");
    
    // Prepare SQL statement with NLP fields and new requirement fields
    $stmt = $conn->prepare("
        INSERT INTO consultations 
        (patient_id, symptoms, duration, severity, age, gender, existing_conditions, input_method, 
         urgency_score, urgency_level, matched_specialty, consultation_mode, attachment_path, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param(
        "isssisssissss",
        $patientId,
        $symptoms,
        $duration,
        $severity,
        $age,
        $gender,
        $existingConditions,
        $inputMethod,
        $urgencyScore,
        $urgencyLevel,
        $matchedSpecialty,
        $consultationMode,
        $attachmentPath
    );
    
    if ($stmt->execute()) {
        $consultationId = $stmt->insert_id;
        $stmt->close();
        
        // ========================================
        // AUTOMATIC DOCTOR MATCHING
        // ========================================
        debug_log("Running Doctor Matching for specialty: $matchedSpecialty");
        require_once 'doctor_matcher.php';
        require_once 'notification_service.php';
        
        $matcher = new DoctorMatcher($conn);
        $language = $input['language'] ?? 'English';
        
        // Try to auto-assign to best matching doctor
        $matchResult = $matcher->autoAssignConsultation($consultationId, $matchedSpecialty, $language, $urgencyLevel);
        
        $autoAssigned = false;
        $assignedDoctor = null;
        
        if ($matchResult['success']) {
            debug_log("Auto-assigned to Doctor ID: " . $matchResult['doctor_id']);
            $autoAssigned = true;
            $assignedDoctor = [
                'id' => $matchResult['doctor_id'],
                'name' => $matchResult['doctor_name'],
                'specialization' => $matchResult['specialization'],
                'match_score' => $matchResult['match_score']
            ];
            
            // Send notification to doctor
            debug_log("Sending notification to doctor...");
            $notificationService = new NotificationService($conn);
            $patientName = $_SESSION['admin_name'] ?? 'Patient';
            $notificationService->notifyDoctorNewConsultation(
                $matchResult['doctor_id'],
                $consultationId,
                $patientName,
                $symptoms
            );
            debug_log("Notification sent.");
        } else {
            debug_log("No auto-assignment made: " . ($matchResult['error'] ?? 'No match found'));
        }
        
        debug_log("Success! Returning response for consultation ID: $consultationId");
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => $autoAssigned ? 
                'Consultation started and assigned to doctor!' : 
                'Consultation started! Waiting for doctor assignment.',
            'consultation_id' => $consultationId,
            'data' => [
                'id' => $consultationId,
                'symptoms' => $symptoms,
                'duration' => $duration,
                'severity' => $severity,
                'status' => $autoAssigned ? 'assigned' : 'pending',
                'input_method' => $inputMethod,
                'urgency_score' => $urgencyScore,
                'urgency_level' => $analysis['urgency_level'],
                'matched_specialty' => $matchedSpecialty,
                'is_emergency' => $isEmergency,
                'auto_assigned' => $autoAssigned,
                'assigned_doctor' => $assignedDoctor
            ]
        ], JSON_PRETTY_PRINT);
        exit;
    } else {
        throw new Exception("Failed to create consultation: " . $stmt->error);
    }
    
} catch (Throwable $e) {
    debug_log("FATAL ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    http_response_code(500);
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
    exit;
}

$conn->close();