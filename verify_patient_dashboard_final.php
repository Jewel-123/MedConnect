<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';
session_start();

$_SESSION['user_id'] = 43;
$_SESSION['role'] = 'patient';

function testApi($url, $patientId) {
    global $conn;
    $_SESSION['user_id'] = $patientId;
    $_SESSION['role'] = 'patient';
    
    ob_start();
    include $url;
    $out = ob_get_clean();
    // Strip headers if any
    $jsonStart = strpos($out, '{');
    if ($jsonStart !== false) {
        $out = substr($out, $jsonStart);
    }
    return json_decode($out, true);
}

echo "=== Testing get_consultations.php (Unified Activity) ===\n";
$res = testApi('get_consultations.php', 43);
if ($res && isset($res['success']) && $res['success']) {
    echo "Count: {$res['count']}\n";
    foreach($res['consultations'] as $item) {
        echo " - ID: {$item['id']} | Type: {$item['type']} | Status: {$item['status']} | Date: {$item['created_at']}\n";
    }
} else {
    echo "API Failed: " . json_encode($res) . "\n";
}
echo "\n";

// Check stats logic from patient_dashboard.php
echo "=== Testing patient_dashboard.php Stats Logic ===\n";
$patientId = 43;
$consultsCountQuery = $conn->query("SELECT COUNT(*) as count FROM consultations WHERE patient_id = $patientId AND status IN ('pending', 'assigned', 'accepted', 'in_progress')");
$apptsCountQuery = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE patient_id = $patientId AND status IN ('pending', 'booked', 'confirmed')");

$activeRequestsCount = ($consultsCountQuery ? $consultsCountQuery->fetch_assoc()['count'] : 0) + 
                       ($apptsCountQuery ? $apptsCountQuery->fetch_assoc()['count'] : 0);

echo "Active Requests Count: $activeRequestsCount\n";
?>
