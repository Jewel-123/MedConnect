<?php
// Mocking session and globals
session_start();
require_once 'db.php';

function logTest($msg, $success = true) {
    echo ($success ? "[PASS] " : "[FAIL] ") . $msg . "\n";
}

try {
    // 1. Setup
    $doctor = $conn->query("SELECT id FROM users WHERE full_name LIKE '%Emily Smith%' AND role = 'doctor'")->fetch_assoc();
    if (!$doctor) throw new Exception("Dr. Emily Smith not found");
    $doctor_id = $doctor['id'];
    
    $patient = $conn->query("SELECT id FROM users WHERE role = 'patient' LIMIT 1")->fetch_assoc();
    if (!$patient) throw new Exception("No patient found for testing");
    $patient_id = $patient['id'];

    echo "Testing with Doctor ID: $doctor_id, Patient ID: $patient_id\n\n";

    // Clean old
    $conn->query("DELETE FROM consultations WHERE patient_id = $patient_id AND doctor_id = $doctor_id");
    $conn->query("DELETE FROM consultation_sessions WHERE patient_id = $patient_id AND doctor_id = $doctor_id");
    
    // Create consultation
    $conn->query("INSERT INTO consultations (patient_id, doctor_id, consultation_fee, payment_status, status, symptoms, consultation_mode) VALUES ($patient_id, $doctor_id, 100.00, 'paid', 'pending', 'Test symptoms', 'video')");
    $consultation_id = $conn->insert_id;
    logTest("Created consultation #$consultation_id");

    // 2. Mock Accept
    $_SESSION['user_id'] = $doctor_id;
    $_SESSION['role'] = 'doctor';
    $_POST = ['action' => 'accept_consultation', 'consultation_id' => $consultation_id];
    
    ob_start();
    include 'doctor_api.php';
    $output = ob_get_clean();
    $res = json_decode($output, true);
    
    $cons = $conn->query("SELECT status FROM consultations WHERE id = $consultation_id")->fetch_assoc();
    logTest("Accepted? Status: " . $cons['status'], $cons['status'] === 'accepted');

    // 3. Mock Start
    $_POST = ['action' => 'start_consultation', 'consultation_id' => $consultation_id];
    ob_start();
    include 'doctor_api.php';
    $output = ob_get_clean();
    $res = json_decode($output, true);
    
    $cons = $conn->query("SELECT status FROM consultations WHERE id = $consultation_id")->fetch_assoc();
    $session = $conn->query("SELECT * FROM consultation_sessions WHERE consultation_id = $consultation_id")->fetch_assoc();
    
    logTest("Started? Status: " . $cons['status'], $cons['status'] === 'in_progress');
    if ($session) {
        logTest("Session found: last_resume_at=" . $session['last_resume_at']);
    } else {
        logTest("Session NOT found", false);
        // Debug the query that was supposed to run
        $session_token = "TEST_TOKEN";
        $sql = "INSERT INTO consultation_sessions (consultation_id, doctor_id, patient_id, session_token, session_type, started_at, last_resume_at, accumulated_seconds) 
                SELECT id, doctor_id, patient_id, '$session_token', consultation_mode, NOW(), NOW(), 0 
                FROM consultations 
                WHERE id = $consultation_id";
        if (!$conn->query($sql)) {
            echo "INSERT FAILED MANUALLY: " . $conn->error . "\n";
        } else {
            echo "INSERT SUCCEEDED MANUALLY. Why did API fail?\n";
        }
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
