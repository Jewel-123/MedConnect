<?php
/**
 * Consultation Messaging API
 * Handle real-time chat messages during consultations
 */

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'db.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please login first']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'patient';

try {
    switch ($action) {
        
        // ==================================================
        // Send chat message
        // ==================================================
        case 'send_message':
            $input = json_decode(file_get_contents('php://input'), true);
            
            $consultationId = $input['consultation_id'] ?? 0;
            $message = trim($input['message'] ?? '');
            
            if (!$consultationId || empty($message)) {
                throw new Exception('Consultation ID and message are required');
            }
            
            // Verify user is part of this consultation
            $stmt = $conn->prepare("
                SELECT patient_id, doctor_id, status
                FROM consultations
                WHERE id = ? AND (patient_id = ? OR doctor_id = ?)
            ");
            $stmt->bind_param("iii", $consultationId, $userId, $userId);
            $stmt->execute();
            $consultation = $stmt->get_result()->fetch_assoc();
            
            if (!$consultation) {
                throw new Exception('Invalid consultation');
            }
            
            if (!in_array($consultation['status'], ['in_progress', 'accepted'])) {
                throw new Exception('Consultation is not active');
            }
            
            // Determine sender role
            $senderRole = ($userId == $consultation['patient_id']) ? 'patient' : 'doctor';
            
            // Insert message (simple encryption - in production use proper encryption)
            $encryptedMessage = base64_encode($message);
            
            $stmt = $conn->prepare("
                INSERT INTO consultation_messages (
                    consultation_id, sender_id, sender_role,
                    message_type, message_content, is_encrypted
                ) VALUES (?, ?, ?, 'text', ?, TRUE)
            ");
            
            $stmt->bind_param("iiss", $consultationId, $userId, $senderRole, $encryptedMessage);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to send message');
            }
            
            $messageId = $stmt->insert_id;
            
            echo json_encode([
                'success' => true,
                'message_id' => $messageId,
                'sent_at' => date('Y-m-d H:i:s')
            ]);
            break;
        
        // ==================================================
        // Get messages for consultation
        // ==================================================
        case 'get_messages':
            $consultationId = $_GET['consultation_id'] ?? 0;
            $since = $_GET['since'] ?? 0; // Message ID to get messages after
            
            // Verify user is part of this consultation
            $stmt = $conn->prepare("
                SELECT id FROM consultations
                WHERE id = ? AND (patient_id = ? OR doctor_id = ?)
            ");
            $stmt->bind_param("iii", $consultationId, $userId, $userId);
            $stmt bind_param("iii", $consultationId, $userId, $userId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception('Invalid consultation');
            }
            
            // Get messages
            $query = "
                SELECT m.*, u.full_name as sender_name
                FROM consultation_messages m
                JOIN users u ON m.sender_id = u.id
                WHERE m.consultation_id = ?
            ";
            
            if ($since > 0) {
                $query .= " AND m.id > $since";
            }
            
            $query .= " ORDER BY m.created_at ASC LIMIT 100";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $consultationId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $messages = [];
            while ($row = $result->fetch_assoc()) {
                // Decrypt message
                $row['message_content'] = base64_decode($row['message_content']);
                $messages[] = $row;
            }
            
            // Mark messages as read
            $conn->query("
                UPDATE consultation_messages
                SET is_read = TRUE, read_at = NOW()
                WHERE consultation_id = $consultationId
                AND sender_id != $userId
                AND is_read = FALSE
            ");
            
            echo json_encode([
                'success' => true,
                'messages' => $messages
            ]);
            break;
        
        // ==================================================
        // Upload file in chat
        // ==================================================
        case 'upload_file':
            if (!isset($_FILES['file']) || !isset($_POST['consultation_id'])) {
                throw new Exception('File and consultation ID are required');
            }
            
            $consultationId = intval($_POST['consultation_id']);
            $file = $_FILES['file'];
            
            // Verify consultation access
            $stmt = $conn->prepare("
                SELECT patient_id, doctor_id FROM consultations
                WHERE id = ? AND (patient_id = ? OR doctor_id = ?)
            ");
            $stmt->bind_param("iii", $consultationId, $userId, $userId);
            $stmt->execute();
            $consultation = $stmt->get_result()->fetch_assoc();
            
            if (!$consultation) {
                throw new Exception('Invalid consultation');
            }
            
            // Validate file
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
            $maxSize = 10 * 1024 * 1024; // 10MB
            
            if ($file['size'] > $maxSize) {
                throw new Exception('File too large. Maximum size is 10MB');
            }
            
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Invalid file type');
            }
            
            // Create upload directory
            $uploadDir = __DIR__ . '/uploads/consultation_files/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'consult_' . $consultationId . '_' . time() . '_' . uniqid() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            $relativePath = 'uploads/consultation_files/' . $filename;
            
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('Failed to upload file');
            }
            
            // Determine category
            $category = 'other';
            if (strpos($file['type'], 'image') !== false) {
                $category = 'image';
            } elseif ($file['type'] === 'application/pdf') {
                $category = 'document';
            }
            
            // Save to database
            $stmt = $conn->prepare("
                INSERT INTO consultation_attachments (
                    consultation_id, uploader_id, file_name, file_path,
                    file_type, file_size, attachment_category
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                "iisssis",
                $consultationId, $userId, $file['name'], $relativePath,
                $file['type'], $file['size'], $category
            );
            
            if (!$stmt->execute()) {
                unlink($filepath);
                throw new Exception('Failed to save file record');
            }
            
            $attachmentId = $stmt->insert_id;
            
            // Create message entry for file
            $senderRole = ($userId == $consultation['patient_id']) ? 'patient' : 'doctor';
            $messageContent = "Shared a file: " . $file['name'];
            
            $stmt = $conn->prepare("
                INSERT INTO consultation_messages (
                    consultation_id, sender_id, sender_role,
                    message_type, message_content, file_reference
                ) VALUES (?, ?, ?, 'file', ?, ?)
            ");
            
            $stmt->bind_param("iisss", $consultationId, $userId, $senderRole, $messageContent, $relativePath);
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'attachment_id' => $attachmentId,
                'file_path' => $relativePath,
                'message' => 'File uploaded successfully'
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