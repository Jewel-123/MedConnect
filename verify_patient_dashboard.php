<?php
require_once 'db.php';
session_start();

$_SESSION['user_id'] = 43;
$_SESSION['role'] = 'patient';

function testAction($url) {
    ob_start();
    include $url;
    return json_decode(ob_get_clean(), true);
}

echo "=== Testing get_consultations.php (Unified Activity) ===\n";
$_GET['action'] = ''; // Not needed for this file
$res = testAction('get_consultations.php');
var_export($res);
echo "\n\n";

// Check stats in patient_dashboard.php
echo "=== Testing patient_dashboard.php Stats ===\n";
// Since patient_dashboard.php is a full HTML page, we'll just simulate the SQL
$patientId = 43;
$activeRequestsQuery = $conn->query("
    (SELECT id FROM consultations WHERE patient_id = $patientId AND status IN ('pending', 'assigned', 'accepted', 'in_progress'))
    UNION
    (SELECT id FROM appointments WHERE patient_id = $patientId AND status IN ('pending', 'booked', 'confirmed'))
");
$activeRequestsCount = $activeRequestsQuery ? $activeRequestsQuery->num_rows : 0;
echo "Active Requests Count: $activeRequestsCount\n";
?>
