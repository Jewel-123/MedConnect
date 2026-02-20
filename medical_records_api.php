<?php
ob_start();
session_start();
require_once 'db.php';

header('Content-Type: application/json');

// Security Check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access. Please log in."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';
$action = $_REQUEST['action'] ?? '';

// Role-based helper
function canManageRecords($role) {
    return in_array($role, ['admin', 'doctor', 'pharmacy']);
}

// Enable mysqli exceptions for robust error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

switch ($action) {
    case 'list':
        try {
            $target_patient_id = $_GET['patient_id'] ?? null;
            
            // Patients can only see their own records
            if ($role === 'patient') {
                $target_patient_id = $user_id;
            }
            
            if (!$target_patient_id) {
                echo json_encode(["status" => "error", "message" => "Patient ID is required."]);
                break;
            }
            
            $sql = "SELECT mr.*, u.full_name as doctor_name 
                    FROM medical_records mr 
                    LEFT JOIN users u ON mr.doctor_id = u.id 
                    WHERE mr.patient_id = ? 
                    ORDER BY mr.visit_date DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $target_patient_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $records = [];
            while ($row = $result->fetch_assoc()) {
                $records[] = $row;
            }
            echo json_encode(["status" => "success", "data" => $records]);
        } catch (mysqli_sql_exception $e) {
            ob_clean();
            echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
        }
        break;

    case 'get':
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) {
                echo json_encode(["status" => "error", "message" => "Record ID is required."]);
                break;
            }
            
            $sql = "SELECT mr.*, u.full_name as doctor_name FROM medical_records mr LEFT JOIN users u ON mr.doctor_id = u.id WHERE mr.id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $record = $stmt->get_result()->fetch_assoc();
            
            if (!$record) {
                echo json_encode(["status" => "error", "message" => "Record not found."]);
                break;
            }
            
            // Security: Patient can only view their own record
            if ($role === 'patient' && $record['patient_id'] != $user_id) {
                echo json_encode(["status" => "error", "message" => "Unauthorized access to this record."]);
                break;
            }
            
            // Also fetch reminders linked to this record
            $reminders_sql = "SELECT * FROM reminders WHERE medical_record_id = ?";
            $r_stmt = $conn->prepare($reminders_sql);
            $r_stmt->bind_param("i", $id);
            $r_stmt->execute();
            $reminders = $r_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $record['reminders'] = $reminders;
            echo json_encode(["status" => "success", "data" => $record]);
        } catch (mysqli_sql_exception $e) {
            ob_clean();
            echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
        }
        break;

    case 'create':
        if (!canManageRecords($role)) {
            echo json_encode(["status" => "error", "message" => "Insufficient permissions."]);
            break;
        }
        
        try {
            $patient_id = $_POST['patient_id'] ?? '';
            $diagnosis = $_POST['diagnosis'] ?? '';
            $medications = $_POST['medications'] ?? '';
            $allergies = $_POST['allergies'] ?? '';
            $lab_results = $_POST['lab_results'] ?? '';
            $visit_date = $_POST['visit_date'] ?? date('Y-m-d');
            $notes = $_POST['notes'] ?? '';
            $doctor_id = ($role === 'doctor') ? $user_id : null;

            if (!$patient_id || !$diagnosis) {
                echo json_encode(["status" => "error", "message" => "Patient ID and Diagnosis are required."]);
                break;
            }

            $sql = "INSERT INTO medical_records (patient_id, doctor_id, diagnosis, medications, allergies, lab_results, visit_date, notes, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iissssssi", $patient_id, $doctor_id, $diagnosis, $medications, $allergies, $lab_results, $visit_date, $notes, $user_id);
            
            if ($stmt->execute()) {
                $record_id = $stmt->insert_id;
                echo json_encode(["status" => "success", "message" => "Record created successfully.", "id" => $record_id]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to create record."]);
            }
        } catch (mysqli_sql_exception $e) {
            ob_clean();
            echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
        }
        break;

    case 'update':
        if (!canManageRecords($role)) {
            echo json_encode(["status" => "error", "message" => "Insufficient permissions."]);
            break;
        }
        
        try {
            $id = $_POST['id'] ?? '';
            $diagnosis = $_POST['diagnosis'] ?? '';
            $medications = $_POST['medications'] ?? '';
            $allergies = $_POST['allergies'] ?? '';
            $lab_results = $_POST['lab_results'] ?? '';
            $visit_date = $_POST['visit_date'] ?? '';
            $notes = $_POST['notes'] ?? '';

            if (!$id || !$diagnosis) {
                echo json_encode(["status" => "error", "message" => "Record ID and Diagnosis are required."]);
                break;
            }

            $sql = "UPDATE medical_records SET diagnosis = ?, medications = ?, allergies = ?, lab_results = ?, visit_date = ?, notes = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssi", $diagnosis, $medications, $allergies, $lab_results, $visit_date, $notes, $id);
            
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Record updated successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to update record."]);
            }
        } catch (mysqli_sql_exception $e) {
            ob_clean();
            echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
        }
        break;

    case 'delete':
        if ($role !== 'admin') {
            echo json_encode(["status" => "error", "message" => "Only administrators can delete records."]);
            break;
        }
        
        try {
            $id = $_POST['id'] ?? '';
            if (!$id) {
                echo json_encode(["status" => "error", "message" => "Record ID is required."]);
                break;
            }
            
            $sql = "DELETE FROM medical_records WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Record deleted successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to delete record."]);
            }
        } catch (mysqli_sql_exception $e) {
            ob_clean();
            echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Invalid action."]);
        break;
}
