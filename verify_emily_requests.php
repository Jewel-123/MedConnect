<?php
require_once 'db.php';

$doctor_id = 10060; // Emily Smith

// Get specialization
$docProfile = $conn->query("SELECT specialization FROM doctor_profiles WHERE user_id = $doctor_id")->fetch_assoc();
$specialization = $docProfile['specialization'] ?? '';
echo "Specialization: '$specialization'\n";

$escapedSpecialty = $conn->real_escape_string($specialization);
$specialtyCondition = " OR (c.doctor_id IS NULL AND LOWER(TRIM(c.matched_specialty)) = LOWER(TRIM('$escapedSpecialty')))";

// Test Consultations Query
$q1 = "
    SELECT c.id, c.status, c.payment_status, c.matched_specialty, c.doctor_id
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

// Stats verification
$pendingConsults = $conn->query("
    SELECT COUNT(*) as count FROM consultations c
    WHERE (c.doctor_id = $doctor_id $specialtyCondition)
      AND c.status IN ('pending', 'assigned')
      AND c.payment_status = 'paid'
")->fetch_assoc()['count'];

echo "Pending Consults Count: $pendingConsults\n";
?>
