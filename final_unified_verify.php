<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require_once 'db.php';

function testDoctorApi($action, $doctor_id) {
    global $conn;
    $_SESSION['user_id'] = $doctor_id;
    $_SESSION['role'] = 'doctor';
    $_GET['action'] = $action;
    ob_start();
    include 'doctor_api.php';
    $output = ob_get_clean();
    $json = substr($output, strpos($output, '{'));
    return json_decode($json, true);
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
$results = "";

$results .= "=== Doctor 10060 (Emily Smith) Dashboard Stats ===\n";
$stats = testDoctorApi('get_dashboard_stats', 10060);
$results .= "Pending Requests Count (Combined): " . ($stats['data']['pending_requests'] ?? 'N/A') . "\n\n";

$results .= "=== Doctor 10060 Incoming Requests ===\n";
$cons = testDoctorApi('get_consultation_requests', 10060);
$appts = testDoctorApi('get_appointment_requests', 10060);
$results .= "Consultations visible: " . (isset($cons['data']) ? count($cons['data']) : 0) . "\n";
$results .= "Appointments visible: " . (isset($appts['data']) ? count($appts['data']) : 0) . "\n\n";

$results .= "=== Doctor 10060 Active Consultations View ===\n";
$active = testDoctorApi('get_active_consultations', 10060);
if (isset($active['data'])) {
    foreach($active['data'] as $item) {
        $results .= " - ID: {$item['id']} | Type: {$item['type']} | Status: {$item['status']} | Urgency: {$item['urgency_level']}\n";
    }
} else {
    $results .= "No active items found.\n";
}
$results .= "\n";

$results .= "=== Patient 43 (Jewel Biju) Activity Feed ===\n";
$activity = testPatientApi(43);
if (isset($activity['consultations'])) {
    foreach(array_slice($activity['consultations'], 0, 5) as $act) {
        $results .= " - Type: {$act['type']} | Status: {$act['status']} | Doctor/Label: {$act['doctor_name']} | Preview: {$act['symptoms_preview']}\n";
    }
}

file_put_contents('final_verify_results.txt', $results);
echo "Verification results saved to final_verify_results.txt";
?>
