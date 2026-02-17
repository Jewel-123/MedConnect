<?php
require_once 'db.php';

// Find Jewel Biju
$patient = $conn->query("SELECT id, full_name FROM users WHERE full_name LIKE '%Jewel%' OR full_name LIKE '%Biju%' LIMIT 1")->fetch_assoc();
if (!$patient) {
    echo "Patient Jewel Biju not found. Creating...\n";
    $conn->query("INSERT INTO users (full_name, email, password, role) VALUES ('Jewel Biju', 'jewel.biju@test.com', 'hashed_password', 'patient')");
    $patient_id = $conn->insert_id;
} else {
    $patient_id = $patient['id'];
    echo "Found patient: {$patient['full_name']} (ID: $patient_id)\n";
}

// Find Dr. Emily Smith
$doctor = $conn->query("SELECT id FROM users WHERE full_name LIKE '%Emily%Smith%' AND role = 'doctor'")->fetch_assoc();
if (!$doctor) {
    die("Dr. Emily Smith not found!\n");
}
$doctor_id = $doctor['id'];
echo "Found doctor: Dr. Emily Smith (ID: $doctor_id)\n";

// Create a realistic consultation
$symptoms = $conn->real_escape_string("Fever, headache, and body aches for 3 days");
$txn_id = "TXN_" . time();

$sql = "INSERT INTO consultations 
    (patient_id, consultation_fee, payment_status, status, symptoms, consultation_mode, payment_transaction_id, created_at, updated_at) 
    VALUES 
    ($patient_id, 100.00, 'paid', 'pending', '$symptoms', 'chat', '$txn_id', NOW(), NOW())";

if ($conn->query($sql)) {
    $consultation_id = $conn->insert_id;
    echo "Created consultation #$consultation_id with symptoms: $symptoms\n";
    echo "\nNow the doctor can see this in Incoming Requests and accept it.\n";
} else {
    echo "Error creating consultation: " . $conn->error . "\n";
}
?>