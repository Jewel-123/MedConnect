<?php
require_once 'db.php';
$consultationId = $_GET['id'] ?? 13; // Default for testing

$res = $conn->query("SELECT patient_id FROM consultations WHERE id = $consultationId");
$c = $res->fetch_assoc();
$patientId = $c['patient_id'];

echo "Consultation ID: $consultationId\n";
echo "Patient ID: $patientId\n";

$prescRes = $conn->query("SELECT COUNT(*) as count FROM prescriptions_v2 WHERE patient_id = $patientId");
echo "Prescriptions (v2) count: " . $prescRes->fetch_assoc()['count'] . "\n";

$prescResOld = $conn->query("SELECT COUNT(*) as count FROM prescriptions WHERE patient_id = $patientId");
echo "Prescriptions (old) count: " . $prescResOld->fetch_assoc()['count'] . "\n";

$vitalsRes = $conn->query("SELECT COUNT(*) as count FROM patient_vitals WHERE patient_id = $patientId");
echo "Vitals count: " . $vitalsRes->fetch_assoc()['count'] . "\n";
?>
