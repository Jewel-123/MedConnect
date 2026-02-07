<?php
/**
 * Enhanced Consultation Chat API
 * Real-time messaging with message classification, workflow guidance, and SOAP notes
 */

session_start();
require_once 'db.php';
require_once 'message_classifier.php';

header('Content-Type: application/json');

// Authentication check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Please log in']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'patient';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        
        // =====================================================
        // SEND MESSAGE (with automatic classification)
        // =====================================================
        case 'send_message':
            $consultation_id = (int)($_POST['consultation_id'] ?? 0);
            $content = trim($_POST['content'] ?? '');
            $type = $_POST['type'] ?? 'text';
            $receiver_id = (int)($_POST['receiver_id'] ?? 0);

            if (!$consultation_id || !$content || !$receiver_id) {
                throw new Exception("Missing required fields");
            }

            // Verify user is part of this consultation
            $authStmt = $conn->prepare("SELECT patient_id, doctor_id FROM consultations WHERE id = ?");
            $authStmt->bind_param("i", $consultation_id);
            $authStmt->execute();
            $authResult = $authStmt->get_result()->fetch_assoc();

            if (!$authResult || ($authResult['patient_id'] != $user_id && $authResult['doctor_id'] != $user_id)) {
                throw new Exception("Unauthorized access to this consultation");
            }

            // Classify message (only for patient messages)
            $classification = 'general';
            $workflow_stage = null;
            $ai_suggestion = null;
            $requires_response = true;

            if ($user_role === 'patient' && $type === 'text') {
                $classifier = new MessageClassifier();
                $classifyResult = $classifier->classify($content);
                
                $classification = $classifyResult['classification'];
                $workflow_stage = $classifyResult['workflow_stage'];
                $requires_response = true;
                
                // Store AI suggestion JSON
                $ai_suggestion = json_encode([
                    'suggested_response' => $classifyResult['suggested_response'],
                    'suggested_questions' => $classifyResult['suggested_questions'],
                    'confidence' => $classifyResult['confidence'],
                    'detected_keywords' => $classifyResult['detected_keywords']
                ]);
            }

            // Insert message into database
            $stmt = $conn->prepare("
                INSERT INTO messages 
                (consultation_id, sender_id, receiver_id, message_content, message_type, 
                 message_classification, workflow_stage, requires_response, ai_suggestion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "iiiisssss", 
                $consultation_id, $user_id, $receiver_id, $content, $type,
                $classification, $workflow_stage, $requires_response, $ai_suggestion
            );
            
            if ($stmt->execute()) {
                $message_id = $stmt->insert_id;
                
                // Log classification for analytics (if classified)
                if ($classification !== 'general' && $type === 'text') {
                    $logStmt = $conn->prepare("
                        INSERT INTO message_classification_log 
                        (message_id, consultation_id, classification_type, confidence_score, 
                         detected_keywords, suggested_response, workflow_stage_detected)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $confidence = $classifyResult['confidence'] ?? 0.5;
                    $keywords_json = json_encode($classifyResult['detected_keywords'] ?? []);
                    $suggested_resp = $classifyResult['suggested_response'] ?? '';
                    
                    $logStmt->bind_param(
                        "iisdss",
                        $message_id, $consultation_id, $classification, $confidence,
                        $keywords_json, $suggested_resp, $workflow_stage
                    );
                    $logStmt->execute();
                }
                
                echo json_encode([
                    'success' => true,
                    'message_id' => $message_id,
                    'classification' => $classification,
                    'workflow_stage' => $workflow_stage
                ]);
            } else {
                throw new Exception("Failed to send message: " . $conn->error);
            }
            break;

        // =====================================================
        // FETCH MESSAGES (AJAX Polling)
        // =====================================================
        case 'fetch_messages':
            $consultation_id = (int)($_GET['consultation_id'] ?? 0);
            $last_id = (int)($_GET['last_id'] ?? 0);

            if (!$consultation_id) {
                throw new Exception("Consultation ID required");
            }

            // Verify user is part of this consultation
            $authStmt = $conn->prepare("SELECT patient_id, doctor_id FROM consultations WHERE id = ?");
            $authStmt->bind_param("i", $consultation_id);
            $authStmt->execute();
            $authResult = $authStmt->get_result()->fetch_assoc();

            if (!$authResult || ($authResult['patient_id'] != $user_id && $authResult['doctor_id'] != $user_id)) {
                throw new Exception("Unauthorized access to this chat");
            }

            // Mark other person's messages as read
            $updateStmt = $conn->prepare("
                UPDATE messages 
                SET is_read = 1, read_at = CURRENT_TIMESTAMP 
                WHERE consultation_id = ? AND receiver_id = ? AND is_read = 0
            ");
            $updateStmt->bind_param("ii", $consultation_id, $user_id);
            $updateStmt->execute();

            // Fetch new messages
            $stmt = $conn->prepare("
                SELECT id, sender_id, message_content, message_type, created_at, is_read, read_at,
                       message_classification, workflow_stage, ai_suggestion
                FROM messages
                WHERE consultation_id = ? AND id > ?
                ORDER BY created_at ASC
            ");
            $stmt->bind_param("ii", $consultation_id, $last_id);
            $stmt->execute();
            $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Parse AI suggestion JSON for doctor view
            if ($user_role === 'doctor') {
                foreach ($messages as &$msg) {
                    if ($msg['ai_suggestion']) {
                        $msg['ai_suggestion'] = json_decode($msg['ai_suggestion'], true);
                    }
                }
            } else {
                // Don't send AI suggestions to patients
                foreach ($messages as &$msg) {
                    unset($msg['ai_suggestion']);
                    unset($msg['message_classification']);
                    unset($msg['workflow_stage']);
                }
            }

            echo json_encode(['success' => true, 'messages' => $messages]);
            break;

        // =====================================================
        // SAVE CLINICAL NOTES (Doctor Only)
        // =====================================================
        case 'save_clinical_notes':
            if ($user_role !== 'doctor') {
                throw new Exception("Only doctors can save clinical notes");
            }

            $consultation_id = (int)($_POST['consultation_id'] ?? 0);
            $soap_notes = $_POST['soap_notes'] ?? null;
            $private_notes = $_POST['private_notes'] ?? null;

            if (!$consultation_id) {
                throw new Exception("Consultation ID required");
            }

            // Verify doctor is assigned to this consultation
            $authStmt = $conn->prepare("SELECT doctor_id FROM consultations WHERE id = ?");
            $authStmt->bind_param("i", $consultation_id);
            $authStmt->execute();
            $authResult = $authStmt->get_result()->fetch_assoc();

            if (!$authResult || $authResult['doctor_id'] != $user_id) {
                throw new Exception("You are not authorized to edit notes for this consultation");
            }

            // Check if notes exist
            $checkStmt = $conn->prepare("SELECT id FROM consultation_clinical_notes WHERE consultation_id = ?");
            $checkStmt->bind_param("i", $consultation_id);
            $checkStmt->execute();
            $exists = $checkStmt->get_result()->num_rows > 0;

            if ($exists) {
                // Update existing notes
                $stmt = $conn->prepare("
                    UPDATE consultation_clinical_notes 
                    SET soap_notes = ?, private_notes = ?, last_autosaved_at = CURRENT_TIMESTAMP
                    WHERE consultation_id = ?
                ");
                $stmt->bind_param("ssi", $soap_notes, $private_notes, $consultation_id);
            } else {
                // Insert new notes
                $stmt = $conn->prepare("
                    INSERT INTO consultation_clinical_notes 
                    (consultation_id, doctor_id, soap_notes, private_notes, last_autosaved_at)
                    VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
                ");
                $stmt->bind_param("iiss", $consultation_id, $user_id, $soap_notes, $private_notes);
            }

            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Clinical notes saved successfully',
                    'autosaved_at' => date('Y-m-d H:i:s')
                ]);
            } else {
                throw new Exception("Failed to save notes: " . $conn->error);
            }
            break;

        // =====================================================
        // GET CLINICAL NOTES (Doctor Only)
        // =====================================================
        case 'get_clinical_notes':
            if ($user_role !== 'doctor') {
                throw new Exception("Only doctors can access clinical notes");
            }

            $consultation_id = (int)($_GET['consultation_id'] ?? 0);

            if (!$consultation_id) {
                throw new Exception("Consultation ID required");
            }

            $stmt = $conn->prepare("
                SELECT soap_notes, private_notes, last_autosaved_at
                FROM consultation_clinical_notes
                WHERE consultation_id = ? AND doctor_id = ?
            ");
            $stmt->bind_param("ii", $consultation_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            if ($result) {
                // Parse SOAP notes JSON
                if ($result['soap_notes']) {
                    $result['soap_notes'] = json_decode($result['soap_notes'], true);
                }
                echo json_encode(['success' => true, 'notes' => $result]);
            } else {
                echo json_encode(['success' => true, 'notes' => null]);
            }
            break;

        // =====================================================
        // GET WORKFLOW GUIDANCE (Doctor Only)
        // =====================================================
        case 'get_workflow_guidance':
            if ($user_role !== 'doctor') {
                throw new Exception("Only doctors can access workflow guidance");
            }

            $consultation_id = (int)($_GET['consultation_id'] ?? 0);

            if (!$consultation_id) {
                throw new Exception("Consultation ID required");
            }

            // Get latest patient message with classification
            $stmt = $conn->prepare("
                SELECT m.*, u.full_name as sender_name
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                JOIN consultations c ON m.consultation_id = c.id
                WHERE m.consultation_id = ? AND m.sender_id = c.patient_id
                ORDER BY m.created_at DESC
                LIMIT 1
            ");
            $stmt->bind_param("i", $consultation_id);
            $stmt->execute();
            $lastMessage = $stmt->get_result()->fetch_assoc();

            $guidance = null;
            if ($lastMessage && $lastMessage['workflow_stage']) {
                // Get workflow template for this stage
                $templateStmt = $conn->prepare("
                    SELECT * FROM workflow_guidance_templates 
                    WHERE workflow_stage = ? AND is_active = 1
                ");
                $templateStmt->bind_param("s", $lastMessage['workflow_stage']);
                $templateStmt->execute();
                $template = $templateStmt->get_result()->fetch_assoc();

                if ($template) {
                    $guidance = [
                        'current_stage' => $lastMessage['workflow_stage'],
                        'classification' => $lastMessage['message_classification'],
                        'stage_description' => $template['stage_description'],
                        'guidance_text' => $template['guidance_text'],
                        'suggested_questions' => json_decode($template['suggested_questions'], true),
                        'example_response' => $template['example_response'],
                        'last_message' => $lastMessage['message_content']
                    ];

                    // Parse AI suggestion if exists
                    if ($lastMessage['ai_suggestion']) {
                        $guidance['ai_suggestion'] = json_decode($lastMessage['ai_suggestion'], true);
                    }
                }
            }

            echo json_encode(['success' => true, 'guidance' => $guidance]);
            break;

        default:
            throw new Exception("Invalid action");
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}