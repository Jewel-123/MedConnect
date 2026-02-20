<?php
require_once 'db.php';

$appointment_id = 87; // Known appointment ID from screenshots

// Simulate doctor session
session_start();

echo "--- Testing Unification Flow for Appointment #$appointment_id ---\n";

// 1. Initial State
$res = $conn->query("SELECT status, doctor_id FROM appointments WHERE id = $appointment_id");
$appt = $res->fetch_assoc();
$_SESSION['user_id'] = $appt['doctor_id'];
$_SESSION['role'] = 'doctor';
$doctor_id = $appt['doctor_id'];

echo "Initial Appointment Status: " . $appt['status'] . "\n";

// 2. Simulate start_consultation via API
$_POST['action'] = 'start_consultation';
$_POST['appointment_id'] = $appointment_id;
$doctor_id = $appt['doctor_id']; // Use the correct doctor ID

echo "Starting consultation via API...\n";
ob_start();
require 'doctor_api.php';
$output = ob_get_clean();
echo "API Output: " . $output . "\n";

$data = json_decode($output, true);
$new_consultation_id = $data['consultation_id'] ?? null;

if ($new_consultation_id) {
    echo "SUCCESS: Consultation $new_consultation_id created/fetched.\n";
    
    // 3. Verify Database
    $res = $conn->query("SELECT status FROM consultations WHERE id = $new_consultation_id");
    $cons = $res->fetch_assoc();
    echo "Consultation Status: " . $cons['status'] . "\n";
    
    $res = $conn->query("SELECT status FROM appointments WHERE id = $appointment_id");
    $appt_later = $res->fetch_assoc();
    echo "Appointment Status After Start: " . $appt_later['status'] . "\n";
} else {
    echo "FAILED: No consultation ID returned.\n";
}

// 4. Test Redirection Trigger (Conceptual)
echo "\nTesting Redirection Logic in consultation_room.php...\n";
// Since we can't easily test headers in CLI, we'll just check if the logic finds the consultation
$stmt = $conn->prepare("SELECT id FROM consultations WHERE appointment_id = ?");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    echo "SUCCESS: Redirection would find consultation ID " . $res->fetch_assoc()['id'] . "\n";
} else {
    echo "FAILED: Redirection would NOT find a consultation.\n";
}

?>
