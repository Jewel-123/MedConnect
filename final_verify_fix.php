<?php
session_start();
require_once 'db.php';

echo "--- Testing Fallback for Appointment 82 ---\n";
$_SESSION['user_id'] = 10060; // Dr. Sophia (assigned to Jewel Biju's latest appt)
$_SESSION['role'] = 'doctor';

ob_start();
$_GET['action'] = 'get_appointment_requests';
require 'doctor_api.php';
$output = ob_get_clean();
$data = json_decode($output, true);

if ($data['status'] === 'success') {
    foreach ($data['data'] as $appt) {
        if ($appt['id'] == 82) {
            echo "Appt 82 for Jewel Biju:\n";
            echo "Notes in DB: " . $appt['notes'] . "\n";
            echo "Displayed Reason: " . $appt['reason'] . "\n";
            echo "Result: " . ($appt['reason'] === 'fever' ? "PASSED (Fallback to consultation working)" : "FAILED (Reason is '{$appt['reason']}')") . "\n";
        }
    }
} else {
    echo "API Error: " . ($data['message'] ?? 'Unknown error') . "\n";
}
?>
