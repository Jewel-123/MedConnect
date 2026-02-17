<?php
require_once 'db.php';

$doctor_id = 10060; // Emily Smith
$output = [];

// Simulate get_dashboard_stats logic
$docProfile = $conn->query("SELECT specialization FROM doctor_profiles WHERE user_id = $doctor_id")->fetch_assoc();
$specialization = $docProfile['specialization'] ?? '';
$escapedSpecialty = $conn->real_escape_string($specialization);
$specialtyCondition = " OR (c.doctor_id IS NULL AND LOWER(TRIM(c.matched_specialty)) = LOWER(TRIM('$escapedSpecialty')))";

$pendingCount = $conn->query("
    SELECT COUNT(*) as count FROM consultations c
    WHERE (c.doctor_id = $doctor_id $specialtyCondition)
      AND c.status IN ('pending', 'assigned')
      AND c.payment_status = 'paid'
")->fetch_assoc()['count'];

$output['pending_badge_count'] = $pendingCount;

// Simulate get_consultation_requests logic
$requests = $conn->query("
    SELECT id, payment_status, status FROM consultations c
    WHERE (c.doctor_id = $doctor_id $specialtyCondition)
      AND c.status IN ('pending', 'assigned')
      AND c.payment_status = 'paid'
");
$output['consultation_requests'] = [];
while($row = $requests->fetch_assoc()) {
    $output['consultation_requests'][] = $row;
}

// Simulate get_appointment_requests logic (confirming ID 68 is now visible)
$appointments = $conn->query("
    SELECT id, status, payment_status FROM appointments a
    WHERE a.doctor_id = $doctor_id 
      AND a.payment_status = 'paid' 
      AND a.status IN ('pending', 'confirmed')
");
$output['appointment_requests'] = [];
while($row = $appointments->fetch_assoc()) {
    $output['appointment_requests'][] = $row;
}

file_put_contents('final_verify.json', json_encode($output, JSON_PRETTY_PRINT));
print_r($output);
?>
