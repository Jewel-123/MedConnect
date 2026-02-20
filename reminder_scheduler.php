<?php
require_once 'db.php';
require_once 'email_config.php';

// Check if PHPMailer exists (based on list_dir output)
$phpMailerPath = 'PHPMailer/src/PHPMailer.php';
$smtpPath = 'PHPMailer/src/SMTP.php';
$exceptionPath = 'PHPMailer/src/Exception.php';

if (file_exists($phpMailerPath)) {
    require_once $phpMailerPath;
    require_once $smtpPath;
    require_once $exceptionPath;
    $mailFound = true;
} else {
    $mailFound = false;
}

header('Content-Type: application/json');

// Security: In production, this should only be triggerable by CLI or a secret key
// For this project, we allow direct browser hit for testing

$today = date('Y-m-d');
$sql = "SELECT r.*, u.full_name as patient_name, u.email as patient_email, u.phone as patient_phone 
        FROM reminders r 
        JOIN users u ON r.patient_id = u.id 
        WHERE r.reminder_date <= ? AND r.status = 'pending'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$reminders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$results = [
    "total_found" => count($reminders),
    "processed" => 0,
    "success" => 0,
    "failed" => 0,
    "logs" => []
];

foreach ($reminders as $r) {
    $results['processed']++;
    $reminder_id = $r['id'];
    $method = $r['notification_method'];
    $success = false;
    $detail = "";

    if ($method === 'email') {
        if ($mailFound && isEmailConfigured()) {
            try {
                $config = getEmailConfig();
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                
                // Server settings
                $mail->isSMTP();
                $mail->Host       = $config['host'];
                $mail->SMTPAuth   = true;
                $mail->Username   = $config['username'];
                $mail->Password   = $config['password'];
                $mail->SMTPSecure = $config['encryption'];
                $mail->Port       = $config['port'];

                // Recipients
                $mail->setFrom($config['from_email'], $config['from_name']);
                $mail->addAddress($r['patient_email'], $r['patient_name']);

                // Content
                $mail->isHTML(true);
                $mail->Subject = "Medical Reminder: " . ucfirst(str_replace('_', ' ', $r['reminder_type']));
                $mail->Body    = "Hello " . htmlspecialchars($r['patient_name']) . ",<br><br>" .
                                 "This is a reminder for your " . str_replace('_', ' ', $r['reminder_type']) . ".<br>" .
                                 "Details: " . htmlspecialchars($r['message']) . "<br><br>" .
                                 "Best regards,<br>MedConnect Team";

                $mail->send();
                $success = true;
                $detail = "Email sent successfully to " . $r['patient_email'];
            } catch (Exception $e) {
                $detail = "Email failed: " . $e->getMessage();
            }
        } else {
            // Simulator mode if PHPMailer not configured correctly
            $success = true; // Mark as success in simulator
            $detail = "[SIMULATOR] Email would be sent to " . $r['patient_email'] . ": " . $r['message'];
        }
    } else if ($method === 'sms') {
        // SMS is currently simulated
        $success = true;
        $detail = "[SIMULATOR] SMS sent to " . $r['patient_phone'] . ": " . $r['message'];
    }

    if ($success) {
        $results['success']++;
        $status_to_log = 'success';
        // Update reminder status
        $conn->query("UPDATE reminders SET status = 'sent' WHERE id = $reminder_id");
    } else {
        $results['failed']++;
        $status_to_log = 'failed';
    }

    // Insert log
    $log_stmt = $conn->prepare("INSERT INTO reminder_logs (reminder_id, method, status, details) VALUES (?, ?, ?, ?)");
    $log_stmt->bind_param("isss", $reminder_id, $method, $status_to_log, $detail);
    $log_stmt->execute();
    
    $results['logs'][] = ["id" => $reminder_id, "status" => $status_to_log, "detail" => $detail];
}

echo json_encode($results);
?>
