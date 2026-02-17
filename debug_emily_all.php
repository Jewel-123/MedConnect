<?php
require_once 'db.php';

$doctor_id = 10060; // Emily Smith
$output = [];

// Get all appointments for this doctor regardless of status
$res = $conn->query("SELECT id, patient_id, status, payment_status, scheduled_date, created_at FROM appointments WHERE doctor_id = $doctor_id ORDER BY created_at DESC LIMIT 50");
$output['all_doctor_appointments'] = [];
while($row = $res->fetch_assoc()) {
    $output['all_doctor_appointments'][] = $row;
}

// Get all consultations for this doctor regardless of status
$res = $conn->query("SELECT id, patient_id, status, payment_status, matched_specialty, created_at FROM consultations WHERE doctor_id = $doctor_id OR (doctor_id IS NULL AND matched_specialty = 'General Physician') ORDER BY created_at DESC LIMIT 50");
$output['all_doctor_consultations'] = [];
while($row = $res->fetch_assoc()) {
    $output['all_doctor_consultations'][] = $row;
}

file_put_contents('emily_all_data.json', json_encode($output, JSON_PRETTY_PRINT));
print_r($output);
?>
