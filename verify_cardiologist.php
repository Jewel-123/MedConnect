<?php
require_once 'db.php';

// 1. Create a dummy unassigned consultation for Cardiologist
$patient_id = 21; // Jewel
$stmt = $conn->prepare("INSERT INTO consultations (patient_id, doctor_id, matched_specialty, symptoms, status, payment_status, created_at) VALUES (?, NULL, 'Cardiologist', 'Heart palpitations', 'pending', 'paid', NOW())");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$new_id = $conn->insert_id;
echo "Created test Cardiologist consultation ID: $new_id\n";

// 2. Simulate James Wilson (ID 26, Cardiologist) checking requests
echo "\n--- Checking for James Wilson (Cardiologist) ---\n";
$doctor_id = 26;
$docProfile = $conn->query("SELECT specialization FROM doctor_profiles WHERE user_id = $doctor_id")->fetch_assoc();
$specialization = $docProfile['specialization'] ?? '';
echo "James's Specialization: $specialization\n";

$specialtyCondition = "";
if ($specialization) {
    $specialtyCondition = " OR (c.doctor_id IS NULL AND c.matched_specialty = '$specialization')";
}

$sql = "SELECT c.id, c.matched_specialty, c.symptoms FROM consultations c WHERE (c.doctor_id = $doctor_id $specialtyCondition) AND c.status IN ('pending', 'assigned') ORDER BY c.created_at DESC LIMIT 5";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) {
    echo "Visible Request: ID " . $row['id'] . " (" . $row['matched_specialty'] . ") - " . $row['symptoms'] . "\n";
}

// 3. Simulate Emily (ID 25, General Physician) - Should NOT see it
echo "\n--- Checking for Emily (General Physician) ---\n";
$doctor_id = 25;
$docProfile = $conn->query("SELECT specialization FROM doctor_profiles WHERE user_id = $doctor_id")->fetch_assoc();
$specialization = $docProfile['specialization'] ?? '';
echo "Emily's Specialization: $specialization\n";

$specialtyCondition = "";
if ($specialization) {
    $specialtyCondition = " OR (c.doctor_id IS NULL AND c.matched_specialty = '$specialization')";
}

$sql = "SELECT c.id, c.matched_specialty, c.symptoms FROM consultations c WHERE (c.doctor_id = $doctor_id $specialtyCondition) AND c.status IN ('pending', 'assigned') ORDER BY c.created_at DESC LIMIT 5";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) {
    echo "Visible Request: ID " . $row['id'] . " (" . $row['matched_specialty'] . ") - " . $row['symptoms'] . "\n";
}
?>
