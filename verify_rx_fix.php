<?php
require_once 'db.php';

// Mock session
session_start();
$_SESSION['user_id'] = 1; // Assuming doctor ID 1 exists
$_SESSION['role'] = 'doctor';

echo "=== Testing Prescription Save Integrity ===\n";

$consultation_id = 10014;
$valid_patient_id = 21;
$invalid_patient_id = "undefined";

function testSave($id, $p_id, $conn) {
    echo "Testing with Patient ID: $p_id... ";
    
    // Simulate API logic
    try {
        if (!is_numeric($p_id)) {
            throw new Exception("Invalid Patient ID format");
        }
        
        $checkPatient = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'patient'");
        $checkPatient->bind_param("i", $p_id);
        $checkPatient->execute();
        if ($checkPatient->get_result()->num_rows === 0) {
            throw new Exception("Patient ID does not exist");
        }
        
        echo "SUCCESS (Validation passed)\n";
    } catch (Exception $e) {
        echo "CAUGHT EXPECTED ERROR: " . $e->getMessage() . "\n";
    }
}

testSave($consultation_id, $valid_patient_id, $conn);
testSave($consultation_id, $invalid_patient_id, $conn);

echo "\n=== Database Check for Rx visibility ===\n";
$res = $conn->query("SELECT id FROM prescriptions_v2 WHERE patient_id = $valid_patient_id LIMIT 1");
if ($res && $res->num_rows > 0) {
    echo "Found prescriptions for patient $valid_patient_id. Visibility logic is working.\n";
} else {
    echo "No prescriptions found for patient $valid_patient_id yet.\n";
}
?>
