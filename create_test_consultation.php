<?php
// Create a test paid consultation for doctor to demonstrate incoming requests
require_once 'db.php';

echo "=== Creating Test Paid Consultation ===\n\n";

// Find a patient
$patient = $conn->query("SELECT id, full_name FROM users WHERE role='patient' LIMIT 1")->fetch_assoc();
if (!$patient) {
    die("No patients found in database!\n");
}

echo "Patient: {$patient['full_name']} (ID: {$patient['id']})\n";

// Doctor ID
$doctor_id = 25;
echo "Doctor ID: $doctor_id\n\n";

// Create consultation
$stmt = $conn->prepare("
    INSERT INTO consultations (
        patient_id, doctor_id, consultation_mode, consultation_fee,
        symptoms, severity, urgency_score,
        language_preference, status, payment_status, created_at
    ) VALUES (?, ?, 'video', 200.00, ?, 'medium', 60, 'English', 'pending', 'paid', NOW())
");

$symptoms = "Test patient with headache and fever for 2 days. Needs consultation.";
$stmt->bind_param("iis", $patient['id'], $doctor_id, $symptoms);

if ($stmt->execute()) {
    $consultation_id = $stmt->insert_id;
    echo "✅ Created consultation ID: $consultation_id\n";
    echo "   Status: pending\n";
    echo "   Payment: paid\n";
    echo "   Fee: $200.00\n\n";
    
    // Verify it shows in the query
    echo "Verifying it appears in incoming requests query:\n";
    $check = $conn->query("
        SELECT id, patient_id, status, payment_status 
        FROM consultations 
        WHERE id = $consultation_id
    ")->fetch_assoc();
    
    echo "  - ID: {$check['id']}\n";
    echo "  - Status: {$check['status']}\n";
    echo "  - Payment: {$check['payment_status']}\n";
    
    if ($check['status'] === 'pending' && $check['payment_status'] === 'paid') {
        echo "\n✅ SUCCESS! This consultation should now appear in incoming requests.\n";
    } else {
        echo "\n❌ ERROR: Status or payment_status not correct!\n";
    }
    
} else {
    echo "❌ Failed to create consultation: " . $stmt->error . "\n";
}