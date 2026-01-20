<?php
require_once 'db.php';

// Get a patient ID
$patientRes = $conn->query("SELECT id FROM users WHERE role = 'patient' LIMIT 1");
$patient = $patientRes->fetch_assoc();
if (!$patient) {
    die("No patient found to create test request.\n");
}
$patient_id = $patient['id'];

// Create a pending consultation
$sql = "INSERT INTO consultations (patient_id, doctor_id, symptoms, severity, urgency_level, urgency_score, consultation_mode, status, matched_specialty, created_at) 
        VALUES ($patient_id, NULL, 'Test symptoms: persistent cough and mild fever.', 'medium', 'urgent', 75, 'text', 'pending', 'General Medicine', NOW())";

if ($conn->query($sql)) {
    echo "TEST CONSULTATION CREATED: ID " . $conn->insert_id . "\n";
} else {
    echo "ERROR: " . $conn->error . "\n";
}
