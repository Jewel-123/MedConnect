<?php
ob_start();
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'list':
        $target_patient_id = $_GET['patient_id'] ?? null;
        if ($role === 'patient') {
            $target_patient_id = $user_id;
        }
        
        if (!$target_patient_id) {
            echo json_encode(["status" => "error", "message" => "Patient ID required."]);
            break;
        }
        
        $sql = "SELECT r.*, mr.diagnosis 
                FROM reminders r 
                LEFT JOIN medical_records mr ON r.medical_record_id = mr.id 
                WHERE r.patient_id = ? 
                ORDER BY r.reminder_date ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $target_patient_id);
        $stmt->execute();
        $reminders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode(["status" => "success", "data" => $reminders]);
        break;

    case 'create':
        if (!in_array($role, ['admin', 'doctor', 'pharmacy'])) {
            echo json_encode(["status" => "error", "message" => "Unauthorized to create reminders."]);
            break;
        }
        
        $patient_id = $_POST['patient_id'] ?? '';
        $mr_id = $_POST['medical_record_id'] ?? null;
        $type = $_POST['reminder_type'] ?? '';
        $date = $_POST['reminder_date'] ?? '';
        $method = $_POST['notification_method'] ?? 'email';
        $message = $_POST['message'] ?? '';

        if (!$patient_id || !$type || !$date) {
            echo json_encode(["status" => "error", "message" => "Required fields missing (patient, type, date)."]);
            break;
        }

        $today = date('Y-m-d');
        $status = ($date > $today) ? 'pending' : 'completed';

        $sql = "INSERT INTO reminders (medical_record_id, patient_id, reminder_type, reminder_date, notification_method, message, status, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisssssi", $mr_id, $patient_id, $type, $date, $method, $message, $status, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Reminder created.", "id" => $stmt->insert_id]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to create reminder: " . $conn->error]);
        }
        break;

    case 'update':
    case 'cancel':
        if (!in_array($role, ['admin', 'doctor', 'pharmacy'])) {
            echo json_encode(["status" => "error", "message" => "Unauthorized."]);
            break;
        }
        
        $id = $_POST['id'] ?? '';
        if (!$id) {
            echo json_encode(["status" => "error", "message" => "ID required."]);
            break;
        }

        if ($action === 'cancel') {
            $sql = "UPDATE reminders SET status = 'cancelled' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
        } else {
            $date = $_POST['reminder_date'] ?? '';
            $method = $_POST['notification_method'] ?? '';
            $status = $_POST['status'] ?? 'pending';
            
            $sql = "UPDATE reminders SET reminder_date = ?, notification_method = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $date, $method, $status, $id);
        }
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Reminder updated."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Update failed."]);
        }
        break;

    case 'delete':
        if (!in_array($role, ['admin', 'doctor'])) {
            echo json_encode(["status" => "error", "message" => "Admin/Doctor only."]);
            break;
        }
        $id = $_POST['id'] ?? '';
        $sql = "DELETE FROM reminders WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Deleted."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Delete failed."]);
        }
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Invalid action."]);
        break;
}
