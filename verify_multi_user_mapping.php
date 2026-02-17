<?php
// Enhanced verification script using subprocesses for clean state
$logFile = __DIR__ . '/verify_direct_log.txt';
file_put_contents($logFile, "Starting Enhanced Verification...\n");

function log_debug($msg) {
    global $logFile;
    file_put_contents($logFile, "[" . date('H:i:s') . "] " . $msg . "\n", FILE_APPEND);
}

require_once 'db.php';

// 1. Setup Test Data
log_debug("Cleaning up old test data...");
$conn->query("DELETE FROM users WHERE email LIKE 'test_doctor_%@example.com'");
$conn->query("DELETE FROM users WHERE email LIKE 'test_patient_%@example.com'");

log_debug("Creating Doctor 1 (Cardiology)");
$conn->query("INSERT INTO users (full_name, email, password, role, status, is_verified) 
              VALUES ('Dr. Heart', 'test_doctor_cardio@example.com', 'hashed_pass', 'doctor', 'approved', 1)");
$doc1Id = $conn->insert_id;
$conn->query("INSERT INTO doctor_profiles (user_id, specialization, license_number) 
              VALUES ($doc1Id, 'Cardiology', 'LIC-HEART')");

log_debug("Creating Doctor 2 (Dermatology)");
$conn->query("INSERT INTO users (full_name, email, password, role, status, is_verified) 
              VALUES ('Dr. Skin', 'test_doctor_skin@example.com', 'hashed_pass', 'doctor', 'approved', 1)");
$doc2Id = $conn->insert_id;
$conn->query("INSERT INTO doctor_profiles (user_id, specialization, license_number) 
              VALUES ($doc2Id, 'Dermatology', 'LIC-SKIN')");

log_debug("Creating Patients and Consultations");
$conn->query("INSERT INTO users (full_name, email, password, role, status, is_verified) 
              VALUES ('Patient 1', 'test_patient_1@example.com', 'hashed_pass', 'patient', 'approved', 1)");
$pat1Id = $conn->insert_id;
$conn->query("INSERT INTO consultations (patient_id, matched_specialty, symptoms, consultation_mode, payment_status, status, created_at) 
              VALUES ($pat1Id, 'Cardiology', 'Chest pain', 'chat', 'paid', 'pending', NOW())");
$cons1Id = $conn->insert_id;

$conn->query("INSERT INTO users (full_name, email, password, role, status, is_verified) 
              VALUES ('Patient 2', 'test_patient_2@example.com', 'hashed_pass', 'patient', 'approved', 1)");
$pat2Id = $conn->insert_id;
$conn->query("INSERT INTO consultations (patient_id, matched_specialty, symptoms, consultation_mode, payment_status, status, created_at) 
              VALUES ($pat2Id, 'Dermatology', 'Red rash', 'chat', 'paid', 'pending', NOW())");
$cons2Id = $conn->insert_id;

// Helper to call API via subprocess
function call_api($userId, $action) {
    $script = "
        \$_SESSION['user_id'] = $userId;
        \$_SESSION['role'] = 'doctor';
        \$_GET['action'] = '$action';
        include 'doctor_api.php';
    ";
    $tmpFile = __DIR__ . '/tmp_test.php';
    file_put_contents($tmpFile, "<?php session_start(); " . $script);
    
    $phpExe = 'C:\\xampp\\php\\php.exe';
    $output = shell_exec("$phpExe $tmpFile 2>&1");
    unlink($tmpFile);
    
    if (($pos = strpos($output, '{')) !== false) {
        $output = substr($output, $pos);
    }
    return json_decode($output, true);
}

// Verify Doctor 1
log_debug("Verifying Doctor 1 (Cardiology)");
$data1 = call_api($doc1Id, 'get_consultation_requests');
$ids1 = array_column($data1['data'] ?? [], 'id');
$success1 = in_array($cons1Id, $ids1) && !in_array($cons2Id, $ids1);
log_debug("Doctor 1 results: " . ($success1 ? "PASSED" : "FAILED") . " (IDs: " . implode(',', $ids1) . ")");

// Verify Doctor 2
log_debug("Verifying Doctor 2 (Dermatology)");
$data2 = call_api($doc2Id, 'get_consultation_requests');
$ids2 = array_column($data2['data'] ?? [], 'id');
$success2 = in_array($cons2Id, $ids2) && !in_array($cons1Id, $ids2);
log_debug("Doctor 2 results: " . ($success2 ? "PASSED" : "FAILED") . " (IDs: " . implode(',', $ids2) . ")");

// Final Cleanup
log_debug("Cleaning up...");
$conn->query("DELETE FROM doctor_profiles WHERE user_id IN ($doc1Id, $doc2Id)");
$conn->query("DELETE FROM users WHERE id IN ($doc1Id, $doc2Id, $pat1Id, $pat2Id)");
$conn->query("DELETE FROM consultations WHERE id IN ($cons1Id, $cons2Id)");

log_debug("FINAL STATUS: " . ($success1 && $success2 ? "PASSED" : "FAILED"));
echo "Verification complete. See verify_direct_log.txt\n";
