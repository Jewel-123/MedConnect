<?php
/**
 * Unified Notification Service
 * Handles Email, SMS, and In-App notifications
 */

require_once 'db.php';
require_once 'email_config.php';
require_once 'sms_gateway.php';

class NotificationService {
    private $conn;
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }
    
    /**
     * Send notification via multiple channels
     * @param int $userId User ID to notify
     * @param string $type Notification type (email, sms, in_app, all)
     * @param string $subject Subject/Title
     * @param string $message Message content
     * @param array $data Additional data (phone, related_id, etc.)
     * @return array Results for each channel
     */
    public function send($userId, $type, $subject, $message, $data = []) {
        $results = [];
        
        // Get user details
        $user = $this->getUserDetails($userId);
        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }
        
        // Send based on type
        if ($type === 'email' || $type === 'all') {
            $results['email'] = $this->sendEmail($user['email'], $subject, $message);
        }
        
        if ($type === 'sms' || $type === 'all') {
            $phone = $data['phone'] ?? $user['phone'] ?? null;
            if ($phone) {
                $results['sms'] = $this->sendSMS($phone, $message);
            } else {
                $results['sms'] = ['success' => false, 'error' => 'No phone number available'];
            }
        }
        
        if ($type === 'in_app' || $type === 'all') {
            $results['in_app'] = $this->sendInAppNotification($userId, $subject, $message, $data);
        }
        
        return $results;
    }
    
    /**
     * Send email notification
     */
    private function sendEmail($to, $subject, $message) {
        try {
            if (!isEmailConfigured()) {
                return ['success' => false, 'error' => 'Email not configured'];
            }
            
            $config = getEmailConfig();
            
            $headers = "From: {$config['from_name']} <{$config['from_email']}>\r\n";
            $headers .= "Reply-To: {$config['from_email']}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            $htmlMessage = $this->getEmailTemplate($subject, $message);
            
            $sent = mail($to, $subject, $htmlMessage, $headers);
            
            // Log notification
            $this->logNotification(0, 'email', $to, $subject, $message, $sent ? 'sent' : 'failed');
            
            return [
                'success' => $sent,
                'error' => $sent ? null : 'Failed to send email'
            ];
            
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Send SMS notification
     */
    private function sendSMS($phone, $message) {
        try {
            $sms = new SimpleSMS();
            
            if (!$sms->isEnabled()) {
                return ['success' => false, 'error' => 'SMS not configured'];
            }
            
            // Format phone number
            $formattedPhone = SimpleSMS::formatPhoneNumber($phone);
            
            // Send SMS (Simulator mode)
            $result = $sms->sendSMS($formattedPhone, $message);
            
            // Log notification (Already happens in logNotification, but we confirm success here)
            $this->logNotification(0, 'sms', $formattedPhone, null, $message, $result['success'] ? 'sent' : 'failed', $result['error'] ?? null);
            
            return $result;
            
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Send in-app notification
     */
    private function sendInAppNotification($userId, $title, $message, $data = []) {
        try {
            $role = $data['role'] ?? 'patient';
            $notificationType = $data['notification_type'] ?? 'system';
            $relatedId = $data['related_id'] ?? null;
            
            // Determine which notification table to use based on role
            if ($role === 'doctor') {
                $table = 'doctor_notifications';
                $userIdColumn = 'doctor_id';
            } else {
                // For now, we'll use doctor_notifications for all
                // In production, you'd have separate tables for patients, pharmacies, etc.
                $table = 'doctor_notifications';
                $userIdColumn = 'doctor_id';
            }
            
            $stmt = $this->conn->prepare("
                INSERT INTO $table ($userIdColumn, notification_type, title, message, related_id, is_read, created_at)
                VALUES (?, ?, ?, ?, ?, FALSE, NOW())
            ");
            
            $stmt->bind_param('isssi', $userId, $notificationType, $title, $message, $relatedId);
            $success = $stmt->execute();
            $stmt->close();
            
            return [
                'success' => $success,
                'error' => $success ? null : 'Failed to create in-app notification'
            ];
            
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get user details
     */
    private function getUserDetails($userId) {
        $stmt = $this->conn->prepare("SELECT id, email, full_name, role FROM users WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        return $user;
    }
    
    /**
     * Log notification attempt
     */
    private function logNotification($userId, $type, $recipient, $subject, $message, $status, $error = null) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO notification_log (user_id, notification_type, recipient, subject, message, status, error_message, sent_at, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->bind_param('issssss', $userId, $type, $recipient, $subject, $message, $status, $error);
            $stmt->execute();
            $stmt->close();
        } catch (Throwable $e) {
            // Silently fail - logging should not break the main flow
            error_log("Failed to log notification: " . $e->getMessage());
        }
    }
    
    /**
     * Get email HTML template
     */
    private function getEmailTemplate($subject, $message) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #0ea5e9; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8fafc; padding: 30px; border-radius: 0 0 8px 8px; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #64748b; }
                .button { display: inline-block; padding: 12px 24px; background: #0ea5e9; color: white; text-decoration: none; border-radius: 6px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🏥 MedConnect</h2>
                </div>
                <div class='content'>
                    <h3>{$subject}</h3>
                    <p>{$message}</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " MedConnect. All rights reserved.</p>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    // ========================================
    // Predefined Notification Templates
    // ========================================
    
    /**
     * Notify doctor of new consultation assignment
     */
    public function notifyDoctorNewConsultation($doctorId, $consultationId, $patientName, $symptoms) {
        $subject = "New Consultation Assigned";
        $message = "You have been assigned a new consultation from {$patientName}.\n\nSymptoms: {$symptoms}\n\nPlease review and respond promptly.";
        
        return $this->send($doctorId, 'all', $subject, $message, [
            'role' => 'doctor',
            'notification_type' => 'new_consultation',
            'related_id' => $consultationId
        ]);
    }
    
    /**
     * Notify pharmacy of new prescription
     */
    public function notifyPharmacyNewPrescription($pharmacyId, $prescriptionId, $patientName, $doctorName, $phone) {
        $subject = "New Prescription Received";
        $message = "New prescription from Dr. {$doctorName} for patient {$patientName}.\n\nPrescription ID: #{$prescriptionId}\n\nPlease prepare the medication.";
        
        return $this->send($pharmacyId, 'all', $subject, $message, [
            'phone' => $phone,
            'notification_type' => 'new_prescription',
            'related_id' => $prescriptionId
        ]);
    }
    
    /**
     * Notify patient of prescription ready
     */
    public function notifyPatientPrescriptionReady($patientId, $prescriptionId, $pharmacyName, $phone) {
        $subject = "Your Prescription is Ready";
        $message = "Your prescription is ready at {$pharmacyName}.\n\nPrescription ID: #{$prescriptionId}\n\nYou can pick it up or request home delivery.";
        
        return $this->send($patientId, 'all', $subject, $message, [
            'phone' => $phone,
            'notification_type' => 'prescription_ready',
            'related_id' => $prescriptionId
        ]);
    }
}

/**
 * Helper function to get notification service instance
 */
function getNotificationService() {
    global $conn;
    return new NotificationService($conn);
}
