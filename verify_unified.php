<?php
session_start();
require_once 'db.php';

echo "<h1>Unified Requests Verification</h1>";

// Mock Emily Smith session (ID 25)
$_SESSION['user_id'] = 25;
$_SESSION['role'] = 'doctor';

echo "<p>Mocked session for Dr. Emily Smith (ID 25)</p>";

// 1. Check API Stats
echo "<h2>1. API Stats Check</h2>";
ob_start();
$_GET['action'] = 'get_dashboard_stats';
include 'doctor_api.php';
$stats_json = ob_get_clean();
$stats = json_decode($stats_json, true);

if ($stats['status'] === 'success') {
    echo "✅ Stats API success. Pending requests: " . $stats['data']['pending_requests'] . "<br>";
} else {
    echo "❌ Stats API failed: " . $stats['message'] . "<br>";
}

// 2. Check Consultation Requests
echo "<h2>2. Consultation Requests Check</h2>";
ob_start();
$_GET['action'] = 'get_consultation_requests';
include 'doctor_api.php';
$cons_json = ob_get_clean();
$cons = json_decode($cons_json, true);

if ($cons['status'] === 'success') {
    echo "✅ Consultation API success. Found: " . count($cons['data']) . "<br>";
} else {
    echo "❌ Consultation API failed: " . $cons['message'] . "<br>";
}

// 3. Check Appointment Requests
echo "<h2>3. Appointment Requests Check</h2>";
ob_start();
$_GET['action'] = 'get_appointment_requests';
include 'doctor_api.php';
$app_json = ob_get_clean();
$app = json_decode($app_json, true);

if ($app['status'] === 'success') {
    echo "✅ Appointment API success. Found: " . count($app['data']) . "<br>";
    foreach ($app['data'] as $a) {
        echo "- ID: {$a['id']}, Patient: {$a['patient_name']}, Status: {$a['payment_status']}<br>";
    }
} else {
    echo "❌ Appointment API failed: " . $app['message'] . "<br>";
}

echo "<hr>";
echo "<p>Total Unified Count (from stats): " . $stats['data']['pending_requests'] . "</p>";
$calculated_total = count($cons['data']) + count($app['data']);
echo "<p>Calculated Total (Cons + App): $calculated_total</p>";

if ($stats['data']['pending_requests'] == $calculated_total) {
    echo "<h3 style='color: green;'>✅ VERIFICATION SUCCESS: Stats match request counts!</h3>";
} else {
    echo "<h3 style='color: red;'>❌ VERIFICATION FAILED: Stats do NOT match request counts!</h3>";
}
?>
