<?php
/**
 * Appointment Management API
 * Handle appointment scheduling, confirmation, and management
 */

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'db.php';
require_once 'notification_service.php';

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
        // Create appointment (patient)
        // ==================================================
        case 'create_appointment':
            $input = json_decode(file_get_contents('php://input'), true);
            
            $doctorId = $input['doctor_id'] ?? 0;
            $scheduledDate = $input['scheduled_date'] ?? '';
            $scheduledTime = $input['scheduled_time'] ?? '';
            $notes = $input['notes'] ?? '';
            
            if (!$doctorId || !$scheduledDate || !$scheduledTime) {
                throw new Exception('Doctor, date, and time are required');
            }
            
            // Validate doctor exists and is approved, and get consultation fee
            $stmt = $conn->prepare("
                SELECT u.id, d.consultation_fee 
                FROM users u
                JOIN doctor_profiles d ON u.id = d.user_id
                WHERE u.id = ? AND u.role = 'doctor' AND u.status = 'approved'
            ");
            $stmt->bind_param("i", $doctorId);
            $stmt->execute();
            $doctor = $stmt->get_result()->fetch_assoc();
            
            if (!$doctor) {
                throw new Exception('Invalid doctor');
            }
            
            $consultationFee = floatval($doctor['consultation_fee']);
            $stmt->close();
            
            // Create appointment with status 'booked' and payment_status 'pending'
            $stmt = $conn->prepare("
                INSERT INTO appointments (
                    patient_id, doctor_id, scheduled_date, scheduled_time,
                    status, payment_status, consultation_fee, notes
                ) VALUES (?, ?, ?, ?, 'booked', 'pending', ?, ?)
            ");
            
            $stmt->bind_param("iissds", $userId, $doctorId, $scheduledDate, $scheduledTime, $consultationFee, $notes);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to create appointment: ' . $stmt->error);
            }
            
            $appointmentId = $stmt->insert_id;
            $stmt->close();
            
            // DO NOT send notification to doctor yet - only after payment
            // Notification will be sent when payment is completed
            
            echo json_encode([
                'success' => true,
                'appointment_id' => $appointmentId,
                'consultation_fee' => $consultationFee,
                'message' => 'Appointment booked. Please complete payment to confirm.'
            ]);
            break;
        
        // ==================================================
        // Get user appointments
        // ==================================================
        case 'get_appointments':
            $status = $_GET['status'] ?? 'all';
            
            if ($userRole === 'patient') {
                $query = "
                    SELECT a.*, 
                           u.full_name as doctor_name,
                           d.specialization,
                           d.consultation_fee
                    FROM appointments a
                    JOIN users u ON a.doctor_id = u.id
                    LEFT JOIN doctor_profiles d ON u.id = d.user_id
                    WHERE a.patient_id = ?
                ";
            } else {
                // For doctors: ONLY show appointments where payment_status = 'paid'
                $query = "
                    SELECT a.*, 
                           u.full_name as patient_name,
                           u.email as patient_email
                    FROM appointments a
                    JOIN users u ON a.patient_id = u.id
                    WHERE a.doctor_id = ? AND a.payment_status = 'paid'
                ";
            }
            
            if ($status !== 'all') {
                $query .= " AND a.status = '$status'";
            }
            
            $query .= " ORDER BY a.scheduled_date DESC, a.scheduled_time DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $appointments = [];
            while ($row = $result->fetch_assoc()) {
                $appointments[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'appointments' => $appointments
            ]);
            break;
        
        // ==================================================
        // Confirm appointment (doctor)
        // ==================================================
        case 'confirm_appointment':
            if ($userRole !== 'doctor') {
                throw new Exception('Only doctors can confirm appointments');
            }
            
            // Accept both JSON and form data
            $input = json_decode(file_get_contents('php://input'), true);
            $appointmentId = $input['appointment_id'] ?? $_POST['appointment_id'] ?? 0;
            
            $stmt = $conn->prepare("
                UPDATE appointments
                SET status = 'confirmed'
                WHERE id = ? AND doctor_id = ? AND status = 'pending'
            ");
            
            $stmt->bind_param("ii", $appointmentId, $userId);
            
            if (!$stmt->execute() || $stmt->affected_rows === 0) {
                throw new Exception('Failed to confirm appointment');
            }
            
            // Get appointment details for notification
            $stmt = $conn->prepare("
                SELECT patient_id, scheduled_date, scheduled_time
                FROM appointments WHERE id = ?
            ");
            $stmt->bind_param("i", $appointmentId);
            $stmt->execute();
            $appt = $stmt->get_result()->fetch_assoc();
            
            // Notify patient
            $notifService = getNotificationService();
            $notifService->send($appt['patient_id'], 'all',
                'Appointment Confirmed',
                "Your appointment on {$appt['scheduled_date']} at {$appt['scheduled_time']} has been confirmed.",
                ['notification_type' => 'appointment_confirmed', 'related_id' => $appointmentId]
            );
            
            // Schedule reminder 24h before
            $reminderTime = date('Y-m-d H:i:s', strtotime("{$appt['scheduled_date']} {$appt['scheduled_time']} -24 hours"));
            $conn->query("
                INSERT INTO scheduled_notifications (
                    user_id, notification_type, schedule_for, related_id, related_type,
                    notification_title, notification_message, delivery_channels
                ) VALUES (
                    {$appt['patient_id']},
                    'appointment_reminder',
                    '$reminderTime',
                    $appointmentId,
                    'appointment',
                    'Appointment Reminder',
                    'You have an appointment tomorrow at {$appt['scheduled_time']}',
                    '[\"email\", \"sms\", \"in_app\"]'
                )
            ");
            
            echo json_encode([
                'success' => true,
                'message' => 'Appointment confirmed'
            ]);
            break;
        
        // ==================================================
        // Cancel appointment
        // ==================================================
        case 'cancel_appointment':
            // Accept both JSON and form data
            $input = json_decode(file_get_contents('php://input'), true);
            $appointmentId = $input['appointment_id'] ?? $_POST['appointment_id'] ?? 0;
            $reason = $input['reason'] ?? $_POST['reason'] ?? '';
            
            // Verify ownership
            if ($userRole === 'patient') {
                $stmt = $conn->prepare("SELECT id FROM appointments WHERE id = ? AND patient_id = ?");
                $stmt->bind_param("ii", $appointmentId, $userId);
            } else {
                $stmt = $conn->prepare("SELECT id FROM appointments WHERE id = ? AND doctor_id = ?");
                $stmt->bind_param("ii", $appointmentId, $userId);
            }
            
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception('Appointment not found');
            }
            $stmt->close();
            
            // Update status
            $stmt = $conn->prepare("
                UPDATE appointments
                SET status = 'cancelled', cancellation_reason = ?
                WHERE id = ?
            ");
            
            $stmt->bind_param("si", $reason, $appointmentId);
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Appointment cancelled'
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
