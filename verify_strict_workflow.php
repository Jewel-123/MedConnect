<?php
require_once 'db.php';

// Helper to log results
function logTest($msg, $success = true) {
    echo ($success ? "[PASS] " : "[FAIL] ") . $msg . "\n";
    flush();
}

try {
    // 1. Setup - Find Emily Smith (Doctor) and a test patient
    $doctor = $conn->query("SELECT id FROM users WHERE full_name LIKE '%Emily Smith%' AND role = 'doctor'")->fetch_assoc();
    if (!$doctor) throw new Exception("Dr. Emily Smith not found");
    $doctor_id = $doctor['id'];
    
    $patient = $conn->query("SELECT id FROM users WHERE role = 'patient' LIMIT 1")->fetch_assoc();
    if (!$patient) throw new Exception("No patient found for testing");
    $patient_id = $patient['id'];

    echo "Testing with Doctor ID: $doctor_id, Patient ID: $patient_id\n\n";

    // 2. Create a paid consultation request
    $conn->query("DELETE FROM consultations WHERE patient_id = $patient_id AND doctor_id = $doctor_id"); // Clean old tests
    $conn->query("INSERT INTO consultations (patient_id, doctor_id, consultation_fee, payment_status, status, symptoms) VALUES ($patient_id, $doctor_id, 100.00, 'paid', 'pending', 'Test symptoms')");
    $consultation_id = $conn->insert_id;
    logTest("Created paid pending consultation #$consultation_id");

    // 3. Accept (Incoming -> Active Not Started)
    $_POST = ['action' => 'accept_consultation', 'consultation_id' => $consultation_id];
    $_SESSION['user_id'] = $doctor_id;
    $_SESSION['role'] = 'doctor';
    
    // Simulate API call (we'll just include doctor_api.php or wrap the logic. Since it's large, we'll manually check logic or include it)
    // For simplicity in this script, we'll just verify the DB after manually 'including' the logic via a separate process or just running the queries as the API would.
    
    // We'll run the actual doctor_api.php via shell to be absolutely sure.
    $cmd = "C:\\xampp\\php\\php.exe doctor_api.php";
    
    function runApi($action, $params = []) {
        global $doctor_id;
        $descriptorspec = [
           0 => ["pipe", "r"],
           1 => ["pipe", "w"],
           2 => ["pipe", "w"]
        ];
        $process = proc_open("C:\\xampp\\php\\php.exe", $descriptorspec, $pipes);
        if (is_resource($process)) {
            $script = "<?php 
            \$_SESSION = ['user_id' => $doctor_id, 'role' => 'doctor'];
            \$_POST = " . var_export(array_merge(['action' => $action], $params), true) . ";
            require 'doctor_api.php';
            ?>";
            fwrite($pipes[0], $script);
            fclose($pipes[0]);
            $out = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            proc_close($process);
            return json_decode($out, true);
        }
        return null;
    }

    // Step: Accept
    echo "Calling accept_consultation...\n";
    $res = runApi('accept_consultation', ['consultation_id' => $consultation_id]);
    echo "API Response: " . json_encode($res) . "\n";
    $cons = $conn->query("SELECT * FROM consultations WHERE id = $consultation_id")->fetch_assoc();
    echo "Consultation State: Status={$cons['status']}, DoctorID={$cons['doctor_id']}\n";
    logTest("Accepted consultation: " . $cons['status'], $cons['status'] === 'accepted');

    // Step: Start
    echo "Calling start_consultation...\n";
    $res = runApi('start_consultation', ['consultation_id' => $consultation_id]);
    echo "API Response: " . json_encode($res) . "\n";
    $cons = $conn->query("SELECT * FROM consultations WHERE id = $consultation_id")->fetch_assoc();
    echo "Consultation State: Status={$cons['status']}\n";
    $session = $conn->query("SELECT * FROM consultation_sessions WHERE consultation_id = $consultation_id")->fetch_assoc();
    if ($session) {
        echo "Session Row Found: last_resume_at=" . ($session['last_resume_at'] ?? 'NULL') . "\n";
    } else {
        echo "ERROR: Session row NOT found in consultation_sessions table.\n";
    }
    logTest("Started consultation: " . $cons['status'], $cons['status'] === 'in_progress');
    logTest("Timer started: last_resume_at = " . $session['last_resume_at'], !empty($session['last_resume_at']));

    // Wait 2 seconds to simulate time passing
    sleep(2);

    // Step: Pause
    $res = runApi('pause_consultation', ['consultation_id' => $consultation_id]);
    $cons = $conn->query("SELECT status FROM consultations WHERE id = $consultation_id")->fetch_assoc();
    $session = $conn->query("SELECT * FROM consultation_sessions WHERE consultation_id = $consultation_id")->fetch_assoc();
    logTest("Paused consultation: " . $cons['status'], $cons['status'] === 'paused');
    logTest("Accumulated seconds: " . $session['accumulated_seconds'], $session['accumulated_seconds'] >= 1);
    logTest("last_resume_at is NULL", is_null($session['last_resume_at']));

    // Step: Resume
    $res = runApi('resume_consultation', ['consultation_id' => $consultation_id]);
    $cons = $conn->query("SELECT status FROM consultations WHERE id = $consultation_id")->fetch_assoc();
    $session = $conn->query("SELECT * FROM consultation_sessions WHERE consultation_id = $consultation_id")->fetch_assoc();
    logTest("Resumed consultation: " . $cons['status'], $cons['status'] === 'in_progress');
    logTest("Timer resumed: last_resume_at = " . $session['last_resume_at'], !empty($session['last_resume_at']));

    sleep(2);

    // Step: Complete
    $res = runApi('complete_consultation', ['consultation_id' => $consultation_id]);
    $cons = $conn->query("SELECT status FROM consultations WHERE id = $consultation_id")->fetch_assoc();
    $session = $conn->query("SELECT * FROM consultation_sessions WHERE consultation_id = $consultation_id")->fetch_assoc();
    $earnings = $conn->query("SELECT payment_status FROM doctor_earnings WHERE consultation_id = $consultation_id")->fetch_assoc();
    
    logTest("Completed consultation: " . $cons['status'], $cons['status'] === 'completed');
    logTest("Total duration minutes: " . $session['duration_minutes'], $session['duration_minutes'] >= 0);
    logTest("Earnings finalized: " . ($earnings['payment_status'] ?? 'N/A'), ($earnings['payment_status'] ?? '') === 'completed');

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
