<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require_once 'db.php';

function testDoctorApi($action, $doctor_id, $postData = []) {
    global $conn;
    $_SESSION['user_id'] = $doctor_id;
    $_SESSION['role'] = 'doctor';
    $_GET['action'] = $action;
    $_POST = $postData;
    
    ob_start();
    include 'doctor_api.php';
    $output = ob_get_clean();
    // Capture full output including errors if JSON fails
    $jsonStart = strpos($output, '{');
    if ($jsonStart !== false) {
        $json = substr($output, $jsonStart);
        return json_decode($json, true) ?: ['status' => 'error', 'message' => 'JSON Decode Failed', 'raw' => $output];
    }
    return ['status' => 'error', 'message' => 'No JSON found', 'raw' => $output];
}

function testPatientApi($patient_id) {
    global $conn;
    $_SESSION['user_id'] = $patient_id;
    $_SESSION['role'] = 'patient';
    
    ob_start();
    include 'get_consultations.php';
    $output = ob_get_clean();
    $json = substr($output, strpos($output, '{'));
    return json_decode($json, true);
}

session_start();
$log = "";

$doctorId = 10060;
$log .= "=== Testing Appointment Confirmation Flow for Doctor $doctorId ===\n";

$reqs = testDoctorApi('get_appointment_requests', $doctorId);
$pendingAppts = $reqs['data'] ?? [];
$log .= "Found " . count($pendingAppts) . " pending appointments.\n";

if (count($pendingAppts) > 0) {
    $appt = $pendingAppts[0];
    $apptId = $appt['id'];
    $patientId = $appt['patient_id'] ?? 0;
    $status = $appt['status'];
    
    if (!$patientId) {
        $log .= "ERROR: Patient ID missing in appointment request data: " . json_encode($appt) . "\n";
    } else {
        $log .= "Testing Appointment ID: $apptId (Patient: $patientId, Status: $status)\n";
        
        $confirmSuccess = false;
        
        if ($status === 'pending') {
            $log .= "Attempting to confirm...\n";
            $confirmRes = testDoctorApi('confirm_appointment', $doctorId, ['appointment_id' => $apptId]);
            $log .= "Confirm Result: " . json_encode($confirmRes) . "\n";
            
            if (($confirmRes['status'] ?? '') === 'success') {
                $log .= "Confirmation successful.\n";
                $confirmSuccess = true;
            } else {
                $log .= "FAILED to confirm appointment. Error: " . ($confirmRes['message'] ?? 'Unknown') . "\n";
                if (isset($confirmRes['raw'])) $log .= "Raw Response: " . $confirmRes['raw'] . "\n";
            }
        } elseif ($status === 'confirmed') {
            $log .= "Appointment already confirmed. Proceeding to verify Active status.\n";
            $confirmSuccess = true;
        }
        
        if ($confirmSuccess) {
            $log .= "Verifying Active Consultations...\n";

            
            $active = testDoctorApi('get_active_consultations', $doctorId);
            $foundInActive = false;
            foreach ($active['data'] ?? [] as $item) {
                if ($item['type'] === 'appointment' && $item['id'] == $apptId) {
                    $foundInActive = true;
                    $log .= "VERIFIED: Appointment $apptId is now in Active Consultations (Status: {$item['status']})\n";
                    break;
                }
            }
            if (!$foundInActive) {
                $log .= "FAILED: Appointment $apptId NOT found in Active Consultations.\n";
                // Debug: Check DB directly
                $dbAppt = $conn->query("SELECT * FROM appointments WHERE id = $apptId")->fetch_assoc();
                $log .= "DB STATE: ID={$dbAppt['id']}, Status={$dbAppt['status']}, PayStatus={$dbAppt['payment_status']}, DocID={$dbAppt['doctor_id']}\n";
                // Dump Active List - FULL RESPONSE
                $log .= "Active List Full Response:\n" . json_encode($active) . "\n";
            }



            
            $activity = testPatientApi($patientId);
            $foundInPatient = false;
            foreach ($activity['consultations'] ?? [] as $act) {
                if ($act['type'] === 'appointment' && $act['id'] == $apptId) {
                    $foundInPatient = true;
                    $log .= "PATIENT VIEW: Appointment $apptId status is '{$act['status']}' and Doctor is '{$act['doctor_name']}'\n";
                    break;
                }
            }
        } else {
            $log .= "FAILED to confirm appointment. Error: " . ($confirmRes['message'] ?? 'Unknown') . "\n";
            if (isset($confirmRes['raw'])) $log .= "Raw Response: " . $confirmRes['raw'] . "\n";
        }
    }
} else {
    $log .= "No pending appointments to test.\n";
}

file_put_contents('test_confirm_result.txt', $log);
echo "Result logged to test_confirm_result.txt";
?>
