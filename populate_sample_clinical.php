<?php
require_once 'db.php';

// Get a patient ID to attach data to
$patientRes = $conn->query("SELECT id FROM users WHERE role = 'patient' LIMIT 1");
$patient = $patientRes->fetch_assoc();
if (!$patient) die("No patient found");
$pid = $patient['id'];

// Get a consultation ID
$conRes = $conn->query("SELECT id FROM consultations WHERE patient_id = $pid LIMIT 1");
$con = $conRes->fetch_assoc();
$cid = $con ? $con['id'] : 'NULL';

// 1. Add Vitals
$conn->query("INSERT INTO patient_vitals (patient_id, consultation_id, weight, height, blood_pressure, temperature, heart_rate, oxygen_level) 
VALUES ($pid, $cid, 72.5, 175.0, '120/80', 36.6, 72, 98)
ON DUPLICATE KEY UPDATE weight = VALUES(weight)");

// 2. Add Medical History
$conn->query("INSERT INTO patient_medical_history (patient_id, record_title, record_type, record_details, record_date)
VALUES ($pid, 'Annual Physical Exam', 'diagnosis', 'Healthy, no major issues', '2025-11-20'),
       ($pid, 'Mild Asthma Flare-up', 'condition', 'Acute asthma', '2025-05-15')");

// 3. Add Reports
$conn->query("INSERT INTO medical_reports (patient_id, report_name, file_path, file_type, description)
VALUES ($pid, 'Blood Test Results - Oct 2025', 'uploads/reports/blood_test.pdf', 'pdf', 'Full blood count'),
       ($pid, 'Chest X-Ray', 'uploads/reports/xray.jpg', 'image', 'Routine screening')");

echo "Sample clinical data added for Patient #$pid";