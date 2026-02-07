<?php
/**
 * Chat API
 * Handles sending and receiving messages for consultations
 */

session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'send':
            $consultation_id = $_POST['consultation_id'];
            $content = $_POST['content'];
            $type = $_POST['type'] ?? 'text'; // 'text', 'image', 'file' or 'signal'
            $receiver_id = $_POST['receiver_id'];

            if (!$consultation_id || !$content || !$receiver_id) {
                throw new Exception("Missing required fields");
            }

            $stmt = $conn->prepare("
                INSERT INTO messages (consultation_id, sender_id, receiver_id, message_content, message_type)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iiiss", $consultation_id, $user_id, $receiver_id, $content, $type);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message_id' => $stmt->insert_id]);
            } else {
                throw new Exception("Failed to send message: " . $conn->error);
            }
            break;

        case 'upload_attachment':
            if (!isset($_FILES['attachment'])) {
                throw new Exception("No file uploaded");
            }

            $file = $_FILES['attachment'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            
            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception("File type not allowed. Please upload an image, PDF, or Word document.");
            }

            if ($file['size'] > 5 * 1024 * 1024) {
                throw new Exception("File is too large. Max size is 5MB.");
            }

            $upload_dir = 'uploads/chat_attachments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid('chat_') . '.' . $file_ext;
            $target_path = $upload_dir . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                $file_type = strpos($file['type'], 'image/') === 0 ? 'image' : 'file';
                echo json_encode([
                    'success' => true, 
                    'file_url' => $target_path,
                    'file_name' => $file['name'],
                    'file_type' => $file_type
                ]);
            } else {
                throw new Exception("Failed to save uploaded file");
            }
            break;

        case 'fetch':
            $consultation_id = $_GET['consultation_id'];
            $last_id = $_GET['last_id'] ?? 0;

            if (!$consultation_id) {
                throw new Exception("Consultation ID required");
            }

            // Verify if user is part of this consultation
            $authStmt = $conn->prepare("SELECT patient_id, doctor_id FROM consultations WHERE id = ?");
            $authStmt->bind_param("i", $consultation_id);
            $authStmt->execute();
            $result = $authStmt->get_result()->fetch_assoc();

            if (!$result || ($result['patient_id'] != $user_id && $result['doctor_id'] != $user_id)) {
                throw new Exception("Unauthorized access to this chat");
            }

            // Mark other person's messages as read
            $updateStmt = $conn->prepare("UPDATE messages SET is_read = 1, read_at = CURRENT_TIMESTAMP WHERE consultation_id = ? AND receiver_id = ? AND is_read = 0");
            $updateStmt->bind_param("ii", $consultation_id, $user_id);
            $updateStmt->execute();

            $stmt = $conn->prepare("
                SELECT id, sender_id, message_content, message_type, created_at, is_read, read_at
                FROM messages
                WHERE consultation_id = ? AND id > ?
                ORDER BY created_at ASC
            ");
            $stmt->bind_param("ii", $consultation_id, $last_id);
            $stmt->execute();
            $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            echo json_encode(['success' => true, 'messages' => $messages]);
            break;

        default:
            throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}