<?php
require_once 'db.php';

$doctor_id = 10062;

// Get specialization
$docProfile = $conn->query("SELECT specialization FROM doctor_profiles WHERE user_id = $doctor_id")->fetch_assoc();
$specialization = $docProfile['specialization'] ?? '';
echo "Specialization: $specialization\n";

$escapedSpecialty = $conn->real_escape_string($specialization);
$specialtyCondition = " OR (c.doctor_id IS NULL AND LOWER(TRIM(c.matched_specialty)) = LOWER(TRIM('$escapedSpecialty')))";

// Test Consultations Query
$q1 = "
    SELECT c.id, c.status, c.payment_status, c.matched_specialty
    FROM consultations c
    WHERE (c.doctor_id = $doctor_id $specialtyCondition)
      AND c.status IN ('pending', 'assigned')
      AND c.payment_status = 'paid'
";
echo "Consultations Query: $q1\n";
$res1 = $conn->query($q1);
while($row = $res1->fetch_assoc()) {
    echo "Found Consultation: " . json_encode($row) . "\n";
}

// Test Appointments Query
$q2 = "
    SELECT a.id, a.status, a.payment_status
    FROM appointments a
    WHERE a.doctor_id = $doctor_id 
      AND a.payment_status = 'paid' 
      AND a.status IN ('pending', 'confirmed')
";
echo "Appointments Query: $q2\n";
$res2 = $conn->query($q2);
while($row = $res2->fetch_assoc()) {
    echo "Found Appointment: " . json_encode($row) . "\n";
}

// Stats verification
$pendingConsults = $conn->query("
    SELECT COUNT(*) as count FROM consultations c
    WHERE (c.doctor_id = $doctor_id $specialtyCondition)
      AND c.status IN ('pending', 'assigned')
      AND c.payment_status = 'paid'
")->fetch_assoc()['count'];

$pendingAppts = $conn->query("
    SELECT COUNT(*) as count FROM appointments a
    WHERE a.doctor_id = $doctor_id 
      AND a.status IN ('pending', 'booked') 
      AND a.payment_status = 'paid'
")->fetch_assoc()['count'];

echo "Pending Consults: $pendingConsults\n";
echo "Pending Appts: $pendingAppts\n";
echo "Total Pending: " . ($pendingConsults + $pendingAppts) . "\n";
?>
