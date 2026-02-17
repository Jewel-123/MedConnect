<?php
require_once 'db.php';

$output = "";

// 2. Simulate Sophia (ID 29, Dermatologist) checking requests
$output .= "\n--- Checking for Sophia (Dermatologist) ---\n";
$doctor_id = 29;
$docProfile = $conn->query("SELECT specialization FROM doctor_profiles WHERE user_id = $doctor_id")->fetch_assoc();
$specialization = $docProfile['specialization'] ?? '';
$output .= "Sophia's Specialization: $specialization\n";

$specialtyCondition = "";
if ($specialization) {
    $specialtyCondition = " OR (c.doctor_id IS NULL AND c.matched_specialty = '$specialization')";
}

$sql = "SELECT c.id, c.matched_specialty, c.symptoms FROM consultations c WHERE (c.doctor_id = $doctor_id $specialtyCondition) AND c.status IN ('pending', 'assigned') ORDER BY c.created_at DESC LIMIT 5";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) {
    $output .= "Visible Request: ID " . $row['id'] . " (" . $row['matched_specialty'] . ") - " . $row['symptoms'] . "\n";
}

// 3. Simulate Emily (ID 25, General Physician) checking requests
$output .= "\n--- Checking for Emily (General Physician) ---\n";
$doctor_id = 25;
$docProfile = $conn->query("SELECT specialization FROM doctor_profiles WHERE user_id = $doctor_id")->fetch_assoc();
$specialization = $docProfile['specialization'] ?? '';
$output .= "Emily's Specialization: $specialization\n";

$specialtyCondition = "";
if ($specialization) {
    $specialtyCondition = " OR (c.doctor_id IS NULL AND c.matched_specialty = '$specialization')";
}

$sql = "SELECT c.id, c.matched_specialty, c.symptoms FROM consultations c WHERE (c.doctor_id = $doctor_id $specialtyCondition) AND c.status IN ('pending', 'assigned') ORDER BY c.created_at DESC LIMIT 5";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) {
    $output .= "Visible Request: ID " . $row['id'] . " (" . $row['matched_specialty'] . ") - " . $row['symptoms'] . "\n";
}

file_put_contents('verify_output.txt', $output);
echo "Verification output written to verify_output.txt";
?>
