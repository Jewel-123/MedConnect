<?php
require_once 'db.php';

// Get a patient ID
$patientRes = $conn->query("SELECT id FROM users WHERE role = 'patient' LIMIT 1");
if (!$patientRes || $patientRes->num_rows == 0) {
    die("No patient found\n");
}
$patient = $patientRes->fetch_assoc();
$patient_id = $patient['id'];

// Create 3 test consultations with different urgency levels
$consultations = [
    ['symptoms' => 'Severe chest pain and shortness of breath', 'severity' => 'high', 'urgency_level' => 'emergency', 'urgency_score' => 95],
    ['symptoms' => 'Persistent headache and dizziness for 3 days', 'severity' => 'medium', 'urgency_level' => 'urgent', 'urgency_score' => 70],
    ['symptoms' => 'Mild cough and runny nose', 'severity' => 'low', 'urgency_level' => 'routine', 'urgency_score' => 30]
];

foreach ($consultations as $c) {
    $sql = "INSERT INTO consultations (patient_id, doctor_id, symptoms, severity, urgency_level, urgency_score, consultation_mode, status, matched_specialty, duration, created_at) 
            VALUES ($patient_id, NULL, '{$c['symptoms']}', '{$c['severity']}', '{$c['urgency_level']}', {$c['urgency_score']}, 'text', 'pending', 'General Medicine', '1-2 days', NOW())";
    
    if ($conn->query($sql)) {
        echo "Created consultation ID: " . $conn->insert_id . " ({$c['urgency_level']})\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
}

// Verify
$res = $conn->query("SELECT COUNT(*) as count FROM consultations WHERE status = 'pending' AND doctor_id IS NULL");
$count = $res->fetch_assoc()['count'];
echo "\nTotal unassigned pending consultations: $count\n";