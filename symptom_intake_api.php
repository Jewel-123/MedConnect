<?php
/**
 * Symptom Intake API
 * Enhanced symptom submission with voice support and file uploads
 */

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'db.php';
require_once 'nlp_symptom_analyzer.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please login first']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];

try {
    switch ($action) {
        
        // ==================================================
        // Submit symptoms with enhanced features
        // ==================================================
        case 'submit_symptoms':
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (empty($input['symptoms'])) {
                throw new Exception('Symptoms description is required');
            }
            
            $symptoms = trim($input['symptoms']);
            $duration = trim($input['duration'] ?? '');
            $severity = $input['severity'] ?? 'moderate';
            $age = !empty($input['age']) ? intval($input['age']) : null;
            $gender = !empty($input['gender']) ? $input['gender'] : null;
            $existingConditions = !empty($input['existing_conditions']) ? trim($input['existing_conditions']) : null;
            $inputMethod = $input['input_method'] ?? 'text'; // 'text' or 'voice'
            $consultationMode = $input['consultation_mode'] ?? 'video';
            $languagePref = $input['language_preference'] ?? 'English';
            
            // NLP Analysis
            $analyzer = new SymptomAnalyzer($conn);
            $analysis = $analyzer->analyze($symptoms);
            
            $urgencyScore = $analysis['urgency_score'];
            $urgencyLevel = $analysis['urgency_level'];
            $matchedSpecialty = $analysis['primary_specialty'];
            
            // CRITICAL FIX: Assign a doctor based on matched specialty
            // This ensures consultations appear in doctor's incoming requests
            $doctorId = null;
            $consultationFee = 0;
            
            if ($matchedSpecialty) {
                $stmt = $conn->prepare("
                    SELECT u.id, dp.consultation_fee
                    FROM users u
                    JOIN doctor_profiles dp ON u.id = dp.user_id
                    WHERE dp.specialization LIKE CONCAT('%', ?, '%')
                      AND u.role = 'doctor'
                      AND u.status = 'approved'
                    ORDER BY RAND()
                    LIMIT 1
                ");
                $stmt->bind_param("s", $matchedSpecialty);
                $stmt->execute();
                $doctor = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                if ($doctor) {
                    $doctorId = $doctor['id'];
                    $consultationFee = floatval($doctor['consultation_fee']);
                }
            }
            
            // Create consultation WITH doctor and fee assigned
            $stmt = $conn->prepare("
                INSERT INTO consultations (
                    patient_id, doctor_id, symptoms, duration, severity, age, gender,
                    existing_conditions, input_method, urgency_score, urgency_level,
                    matched_specialty, consultation_mode, language_preference, 
                    consultation_fee, payment_status, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')
            ");
            
            $stmt->bind_param(
                "iisssissississd",
                $userId, $doctorId, $symptoms, $duration, $severity, $age, $gender,
                $existingConditions, $inputMethod, $urgencyScore, $urgencyLevel,
                $matchedSpecialty, $consultationMode, $languagePref,
                $consultationFee
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to create consultation');
            }
            
            $consultationId = $stmt->insert_id;
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'consultation_id' => $consultationId,
                'analysis' => $analysis,
                'message' => 'Symptoms submitted successfully'
            ]);
            break;
        
        // ==================================================
        // Upload medical files/images
        // ==================================================
        case 'upload_attachment':
            if (!isset($_FILES['file']) || !isset($_POST['consultation_id'])) {
                throw new Exception('File and consultation ID are required');
            }
            
            $consultationId = intval($_POST['consultation_id']);
            $file = $_FILES['file'];
            
            // Verify consultation belongs to user
            $stmt = $conn->prepare("SELECT id FROM consultations WHERE id = ? AND patient_id = ?");
            $stmt->bind_param("ii", $consultationId, $userId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception('Invalid consultation');
            }
            $stmt->close();
            
            // Validate file
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            if ($file['size'] > $maxSize) {
                throw new Exception('File too large. Maximum size is 5MB');
            }
            
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Invalid file type. Allowed: JPG, PNG, PDF');
            }
            
            // Create upload directory
            $uploadDir = __DIR__ . '/uploads/symptom_attachments/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'symptom_' . $consultationId . '_' . time() . '_' . uniqid() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            $relativePath = 'uploads/symptom_attachments/' . $filename;
            
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('Failed to upload file');
            }
            
            // Determine attachment type
            $attachmentType = 'other';
            if (strpos($file['type'], 'image') !== false) {
                $attachmentType = 'image';
            } elseif ($file['type'] === 'application/pdf') {
                $attachmentType = 'document';
            }
            
            // Save to database
            $stmt = $conn->prepare("
                INSERT INTO symptom_attachments (
                    consultation_id, patient_id, file_name, file_path,
                    file_type, file_size, attachment_type
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                "iisssis",
                $consultationId, $userId, $file['name'], $relativePath,
                $file['type'], $file['size'], $attachmentType
            );
            
            if (!$stmt->execute()) {
                // Delete uploaded file if database insert fails
                unlink($filepath);
                throw new Exception('Failed to save attachment record');
            }
            
            $attachmentId = $stmt->insert_id;
            $stmt->close();
            
            // Update attachment count
            $conn->query("
                UPDATE consultations 
                SET attachment_count = (SELECT COUNT(*) FROM symptom_attachments WHERE consultation_id = $consultationId)
                WHERE id = $consultationId
            ");
            
            echo json_encode([
                'success' => true,
                'attachment_id' => $attachmentId,
                'file_path' => $relativePath,
                'message' => 'File uploaded successfully'
            ]);
            break;
        
        // ==================================================
        // Get Advanced AI Analysis
        // ==================================================
        case 'get_ai_analysis':
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (empty($input['symptoms'])) {
                throw new Exception('Symptoms description is required');
            }
            
            // Prepare context
            $context = [];
            if (!empty($input['age'])) {
                $context['age'] = intval($input['age']);
            }
            if (!empty($input['gender'])) {
                $context['gender'] = $input['gender'];
            }
            if (!empty($input['existing_conditions'])) {
                $context['existing_conditions'] = $input['existing_conditions'];
            }
            
            // Run AI analysis
            require_once 'medical_ai_engine.php';
            $aiEngine = new MedicalAIEngine($conn);
            $analysis = $aiEngine->analyze($input['symptoms'], $context);
            
            // Log analysis if consultation_id provided
            if (!empty($input['consultation_id'])) {
                $aiEngine->logAnalysis($input['consultation_id'], $userId, $analysis);
            }
            
            echo json_encode([
                'success' => true,
                'analysis' => $analysis,
                'message' => 'AI analysis completed successfully'
            ]);
            break;
        
        // ==================================================
        // Get symptom suggestions (autocomplete)
        // ==================================================
        case 'get_suggestions':
            $query = $_GET['query'] ?? '';
            
            if (strlen($query) < 2) {
                echo json_encode(['success' => true, 'suggestions' => []]);
                exit;
            }
            
            $stmt = $conn->prepare("
                SELECT DISTINCT keyword, description, urgency_level
                FROM symptom_keywords
                WHERE keyword LIKE CONCAT('%', ?, '%')
                ORDER BY urgency_score DESC, keyword ASC
                LIMIT 10
            ");
            
            $stmt->bind_param("s", $query);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $suggestions = [];
            while ($row = $result->fetch_assoc()) {
                $suggestions[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'suggestions' => $suggestions
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