<?php
/**
 * Notification Scheduler (Cron Job)
 * Process scheduled notifications and send reminders
 * 
 * Add to Windows Task Scheduler or cron:
 * */5 * * * * php C:\xampp\htdocs\MedConnect\notification_scheduler.php
 */

require_once 'db.php';
require_once 'notification_service.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting notification scheduler...\n";

$notifService = new NotificationService($conn);

// Get all pending scheduled notifications that are due
$now = date('Y-m-d H:i:s');
$stmt = $conn->prepare("
    SELECT * FROM scheduled_notifications
    WHERE status = 'pending' AND schedule_for <= ?
    LIMIT 100
");

$stmt->bind_param("s", $now);
$stmt->execute();
$result = $stmt->get_result();

$sent = 0;
$failed = 0;

while ($notification = $result->fetch_assoc()) {
    echo "Processing notification ID: {$notification['id']} for user: {$notification['user_id']}\n";
    
    try {
        // Parse delivery channels
        $channels = json_decode($notification['delivery_channels'], true);
        if (!$channels || !is_array($channels)) {
            $channels = ['in_app']; // Default to in-app
        }
        
        // Send notification
        $deliveryType = 'all'; // Will check preferences internally
        if (count($channels) === 1) {
            $deliveryType = $channels[0];
        }
        
        $result = $notifService->send(
            $notification['user_id'],
            $deliveryType,
            $notification['notification_title'],
            $notification['notification_message'],
            [
                'related_id' => $notification['related_id'],
                'notification_type' => $notification['notification_type']
            ]
        );
        
        // Update status
        $conn->query("
            UPDATE scheduled_notifications
            SET status = 'sent', sent_at = NOW()
            WHERE id = {$notification['id']}
        ");
        
        echo "✓ Sent successfully\n";
        $sent++;
        
    } catch (Exception $e) {
        echo "✗ Failed: " . $e->getMessage() . "\n";
        
        // Update with error
        $errorMsg = $conn->real_escape_string($e->getMessage());
        $conn->query("
            UPDATE scheduled_notifications
            SET status = 'failed', error_message = '$errorMsg'
            WHERE id = {$notification['id']}
        ");
        
        $failed++;
    }
}

echo "\n[" . date('Y-m-d H:i:s') . "] Scheduler completed.\n";
echo "Sent: $sent, Failed: $failed\n";

// ==================================================
// Auto-generate appointment reminders (24h before)
// ==================================================

echo "\n[" . date('Y-m-d H:i:s') . "] Checking for upcoming appointments...\n";

// Find appointments in next 24-25 hours that haven't been reminded
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$dayAfter = date('Y-m-d', strtotime('+2 days'));

$appointments = $conn->query("
    SELECT a.*, 
           u.full_name as patient_name,
           doc.full_name as doctor_name,
           dp.specialization
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    JOIN users doc ON a.doctor_id = doc.id
    LEFT JOIN doctor_profiles dp ON doc.id = dp.user_id
    WHERE a.status = 'confirmed'
    AND a.scheduled_date BETWEEN '$tomorrow' AND '$dayAfter'
    AND a.reminder_sent = FALSE
");

$remindersCreated = 0;

while ($appointment = $appointments->fetch_assoc()) {
    $msg = "Reminder: You have an appointment with Dr. {$appointment['doctor_name']} ({$appointment['specialization']}) tomorrow at {$appointment['scheduled_time']}. Please be on time.";
    
    // Create scheduled notification for 8 AM tomorrow
    $reminderTime = "$tomorrow 08:00:00";
    
    $stmt = $conn->prepare("
        INSERT INTO scheduled_notifications (
            user_id, notification_type, schedule_for, related_id, related_type,
            notification_title, notification_message, delivery_channels, status
        ) VALUES (?, 'appointment_reminder', ?, ?, 'appointment', 'Appointment Reminder Tomorrow', ?, ?, 'pending')
    ");
    
    $channels = '["email", "sms", "in_app"]';
    $stmt->bind_param(
        "isiss",
        $appointment['patient_id'],
        $reminderTime,
        $appointment['id'],
        $msg,
        $channels
    );
    
    if ($stmt->execute()) {
        // Mark as reminded
        $conn->query("
            UPDATE appointments
            SET reminder_sent = TRUE, reminder_sent_at = NOW()
            WHERE id = {$appointment['id']}
        ");
        
        echo "✓ Created reminder for appointment ID: {$appointment['id']}\n";
        $remindersCreated++;
    }
}

echo "Created $remindersCreated appointment reminders\n";

// ==================================================
// Medication refill reminders
// ==================================================
// TODO: Implement based on prescription duration

echo "\n[" . date('Y-m-d H:i:s') . "] Scheduler finished.\n";

$conn->close();
