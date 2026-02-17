<?php
require_once 'db.php';

echo "Starting historical data restoration...\n\n";

// Get user IDs
$jewel = $conn->query("SELECT id FROM users WHERE full_name LIKE '%JEWEL%BIJU%' LIMIT 1")->fetch_assoc();
$emily = $conn->query("SELECT id FROM users WHERE full_name LIKE '%Emily%Smith%' AND role = 'doctor' LIMIT 1")->fetch_assoc();

if (!$jewel || !$emily) {
    die("Error: Required users not found\n");
}

$jewel_id = $jewel['id'];
$emily_id = $emily['id'];

echo "Found Jewel Biju (ID: $jewel_id) and Dr. Emily Smith (ID: $emily_id)\n\n";

// Consultation 1: Feb 10
echo "Restoring Consultation 1 (Feb 10)...\n";
$result = $conn->query("INSERT INTO consultations (patient_id, doctor_id, symptoms, status, payment_status, consultation_mode, consultation_fee, created_at, updated_at)
VALUES ($jewel_id, $emily_id, 'Persistent cough and mild fever for 5 days', 'completed', 'paid', 'chat', 100.00, '2026-02-10 10:30:00', '2026-02-10 11:15:00')");

if (!$result) {
    echo "Error: " . $conn->error . "\n";
} else {
    $consult1_id = $conn->insert_id;
    echo "  ✓ Consultation #$consult1_id created\n";
    
    $conn->query("INSERT INTO prescriptions_v2 (consultation_id, patient_id, doctor_id, diagnosis, status, created_at)
    VALUES ($consult1_id, $jewel_id, $emily_id, 'Upper Respiratory Tract Infection', 'finalized', '2026-02-10 11:10:00')");
    
    $conn->query("INSERT INTO doctor_earnings (doctor_id, consultation_id, gross_amount, platform_commission_percent, platform_commission_amount, net_amount, payment_status, created_at)
    VALUES ($emily_id, $consult1_id, 100.00, 10.00, 10.00, 90.00, 'completed', '2026-02-10 11:15:00')");
}

// Consultation 2: Feb 11
echo "Restoring Consultation 2 (Feb 11)...\n";
$result = $conn->query("INSERT INTO consultations (patient_id, doctor_id, symptoms, status, payment_status, consultation_mode, consultation_fee, created_at, updated_at)
VALUES ($jewel_id, $emily_id, 'Headache and dizziness', 'completed', 'paid', 'video', 100.00, '2026-02-11 14:00:00', '2026-02-11 14:45:00')");

if (!$result) {
    echo "Error: " . $conn->error . "\n";
} else {
    $consult2_id = $conn->insert_id;
    echo "  ✓ Consultation #$consult2_id created\n";
    
    $conn->query("INSERT INTO prescriptions_v2 (consultation_id, patient_id, doctor_id, diagnosis, status, created_at)
    VALUES ($consult2_id, $jewel_id, $emily_id, 'Tension Headache', 'finalized', '2026-02-11 14:40:00')");
    
    $conn->query("INSERT INTO doctor_earnings (doctor_id, consultation_id, gross_amount, platform_commission_percent, platform_commission_amount, net_amount, payment_status, created_at)
    VALUES ($emily_id, $consult2_id, 100.00, 10.00, 10.00, 90.00, 'completed', '2026-02-11 14:45:00')");
}

// Consultation 3: Feb 12
echo "Restoring Consultation 3 (Feb 12)...\n";
$result = $conn->query("INSERT INTO consultations (patient_id, doctor_id, symptoms, status, payment_status, consultation_mode, consultation_fee, created_at, updated_at)
VALUES ($jewel_id, $emily_id, 'Stomach pain and nausea', 'completed', 'paid', 'chat', 100.00, '2026-02-12 09:00:00', '2026-02-12 09:50:00')");

if (!$result) {
    echo "Error: " . $conn->error . "\n";
} else {
    $consult3_id = $conn->insert_id;
    echo "  ✓ Consultation #$consult3_id created\n";
    
    $conn->query("INSERT INTO prescriptions_v2 (consultation_id, patient_id, doctor_id, diagnosis, status, created_at)
    VALUES ($consult3_id, $jewel_id, $emily_id, 'Gastritis', 'finalized', '2026-02-12 09:45:00')");
    
    $conn->query("INSERT INTO doctor_earnings (doctor_id, consultation_id, gross_amount, platform_commission_percent, platform_commission_amount, net_amount, payment_status, created_at)
    VALUES ($emily_id, $consult3_id, 100.00, 10.00, 10.00, 90.00, 'completed', '2026-02-12 09:50:00')");
}

// Summary
echo "\n=== RESTORATION COMPLETE ===\n";
$consultations = $conn->query("SELECT COUNT(*) as count FROM consultations WHERE patient_id = $jewel_id AND doctor_id = $emily_id AND status = 'completed'")->fetch_assoc();
$prescriptions = $conn->query("SELECT COUNT(*) as count FROM prescriptions_v2 WHERE patient_id = $jewel_id AND doctor_id = $emily_id")->fetch_assoc();
$earnings = $conn->query("SELECT SUM(net_amount) as total FROM doctor_earnings WHERE doctor_id = $emily_id")->fetch_assoc();

echo "Completed consultations restored: {$consultations['count']}\n";
echo "Prescriptions restored: {$prescriptions['count']}\n";
echo "Total earnings: ₹" . ($earnings['total'] ?? 0) . "\n";
echo "\nRefresh your doctor dashboard to see the restored data!\n";
?>
