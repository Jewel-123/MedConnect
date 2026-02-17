<?php
require_once 'db.php';

echo "=== System-wide Consultation Match Check ===\n";

// Get all doctors
$doctors = [];
$res = $conn->query("SELECT u.id, u.full_name, d.specialization FROM users u JOIN doctor_profiles d ON u.id = d.user_id WHERE u.role = 'doctor'");
while($row = $res->fetch_assoc()) {
    $doctors[] = $row;
}

// Get all paid, pending/assigned consultations
$cons = [];
$res = $conn->query("SELECT id, doctor_id, matched_specialty, patient_id FROM consultations WHERE payment_status = 'paid' AND status IN ('pending', 'assigned')");
while($row = $res->fetch_assoc()) {
    $cons[] = $row;
}

echo "Total Doctors: " . count($doctors) . "\n";
echo "Total Paid/Pending Consults: " . count($cons) . "\n\n";

foreach($doctors as $doc) {
    echo "Doctor: {$doc['full_name']} (ID: {$doc['id']}, Specialty: '{$doc['specialization']}')\n";
    $matches = 0;
    foreach($cons as $c) {
        $doctor_id = $doc['id'];
        $specialization = $doc['specialization'];
        $escapedSpecialty = $conn->real_escape_string($specialization);
        $specialtyCondition = "(doctor_id = $doctor_id OR (doctor_id IS NULL AND LOWER(TRIM(matched_specialty)) = LOWER(TRIM('$escapedSpecialty'))))";
        
        $q = "SELECT id FROM consultations WHERE id = {$c['id']} AND $specialtyCondition AND status IN ('pending', 'assigned') AND payment_status = 'paid'";
        $check = $conn->query($q);
        
        if ($check && $check->num_rows > 0) {
            echo "  - MATCHES Consult ID: {$c['id']} (Specialty: '{$c['matched_specialty']}')\n";
            $matches++;
        }
    }
    if ($matches === 0) echo "  - No matches found.\n";
    echo "\n";
}
?>
