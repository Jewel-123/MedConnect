<?php
session_start();
require_once 'db.php';

// Simulate being logged in as Sophia Martinez (doctor ID 19)
$_SESSION['user_id'] = 19;
$_SESSION['role'] = 'doctor';
$doctor_id = 19;

// Run the exact same query the dashboard uses
$requests = $conn->query("
    SELECT c.*, u.full_name as patient_name, u.email as patient_email, u.phone as patient_phone,
           TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) as patient_age, p.gender as patient_gender
    FROM consultations c
    JOIN users u ON c.patient_id = u.id
    LEFT JOIN patient_profiles p ON u.id = p.user_id
    WHERE c.status IN ('pending', 'reassigned') 
      AND (c.doctor_id IS NULL OR c.doctor_id = $doctor_id)
    ORDER BY 
        CASE WHEN c.urgency_level = 'emergency' THEN 1 
             WHEN c.urgency_level = 'urgent' THEN 2 
             ELSE 3 END, 
        c.urgency_score DESC, 
        c.created_at ASC
    LIMIT 20
");

echo "CONSULTATIONS FOR SOPHIA MARTINEZ (Doctor ID 19):\n";
echo "=================================================\n\n";

$count = 0;
while ($row = $requests->fetch_assoc()) {
    $count++;
    echo "Consultation #{$count}:\n";
    echo "  ID: {$row['id']}\n";
    echo "  Patient: {$row['patient_name']}\n";
    echo "  Symptoms: " . substr($row['symptoms'], 0, 50) . "...\n";
    echo "  Urgency: {$row['urgency_level']} (Score: {$row['urgency_score']})\n";
    echo "  Status: {$row['status']}\n";
    echo "  Mode: {$row['consultation_mode']}\n";
    echo "\n";
}

if ($count == 0) {
    echo "❌ NO REQUESTS FOUND\n\n";
    echo "Debugging:\n";
    
    // Check if consultation 59 exists
    $check = $conn->query("SELECT id, doctor_id, status, urgency_level FROM consultations WHERE id = 59");
    if ($check && $c = $check->fetch_assoc()) {
        echo "Consultation 59 exists:\n";
        echo "  - Doctor ID: {$c['doctor_id']}\n";
        echo "  - Status: '{$c['status']}'\n";
        echo "  - Urgency: {$c['urgency_level']}\n";
    } else {
        echo "Consultation 59 NOT FOUND\n";
    }
} else {
    echo "✓ FOUND $count REQUEST(S)\n";
}
