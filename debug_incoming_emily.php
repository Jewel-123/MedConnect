<?php
require_once 'db.php';

$doctor_id = 10060; // Emily Smith
$output = [];

$doc = $conn->query("SELECT u.full_name, d.specialization FROM users u JOIN doctor_profiles d ON u.id = d.user_id WHERE u.id = $doctor_id")->fetch_assoc();
if ($doc) {
    $output['doctor'] = $doc;
    $specialization = $doc['specialization'];
} else {
    $output['error'] = 'Doctor not found';
    file_put_contents('emily_debug.json', json_encode($output, JSON_PRETTY_PRINT));
    exit;
}

$escapedSpecialty = $conn->real_escape_string($specialization);
$specialtyCondition = " OR (doctor_id IS NULL AND LOWER(TRIM(matched_specialty)) = LOWER(TRIM('$escapedSpecialty')))";

$sql = "SELECT id, doctor_id, matched_specialty, status, payment_status, created_at FROM consultations 
        WHERE (doctor_id = $doctor_id $specialtyCondition) 
        AND status IN ('pending', 'assigned')";
$res = $conn->query($sql);
$output['consultations'] = [];
while($row = $res->fetch_assoc()) {
    $output['consultations'][] = $row;
}

$sql = "SELECT id, doctor_id, status, payment_status, scheduled_date FROM appointments 
        WHERE doctor_id = $doctor_id 
        AND payment_status = 'paid' 
        AND status IN ('pending', 'confirmed')";
$res = $conn->query($sql);
$output['appointments'] = [];
while($row = $res->fetch_assoc()) {
    $output['appointments'][] = $row;
}

// Also check ALL consultations to see if there's a specialized request that isn't showing
$output['all_recent_consultations'] = [];
$res = $conn->query("SELECT id, doctor_id, matched_specialty, status, payment_status FROM consultations ORDER BY created_at DESC LIMIT 20");
while($row = $res->fetch_assoc()) {
    $output['all_recent_consultations'][] = $row;
}

file_put_contents('emily_debug.json', json_encode($output, JSON_PRETTY_PRINT));
$conn->close();
?>
