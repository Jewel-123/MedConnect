<?php
require_once 'db.php';

// Find Sophia Martinez's doctor ID
$res = $conn->query("SELECT id, full_name, email FROM users WHERE full_name LIKE '%Sophia%' AND role = 'doctor'");
if ($res && $row = $res->fetch_assoc()) {
    $doctor_id = $row['id'];
    echo "Found doctor: {$row['full_name']} (ID: $doctor_id)\n";
    
    // Get a patient ID
    $patientRes = $conn->query("SELECT id FROM users WHERE role = 'patient' LIMIT 1");
    if ($patientRes && $patient = $patientRes->fetch_assoc()) {
        $patient_id = $patient['id'];
        
        // Create a consultation specifically for Sophia
        $sql = "INSERT INTO consultations 
                (patient_id, doctor_id, symptoms, severity, urgency_level, urgency_score, 
                 consultation_mode, status, matched_specialty, duration, created_at) 
                VALUES 
                ($patient_id, $doctor_id, 'Severe skin rash with itching and redness that started yesterday', 
                 'high', 'urgent', 80, 'video', 'pending', 'Dermatology', '1-2 days', NOW())";
        
        if ($conn->query($sql)) {
            $consult_id = $conn->insert_id;
            echo "✓ Created consultation ID: $consult_id\n";
            echo "  - Patient ID: $patient_id\n";
            echo "  - Doctor ID: $doctor_id\n";
            echo "  - Status: pending\n";
            echo "  - Urgency: urgent (score 80)\n";
            echo "  - Specialty: Dermatology\n";
            
            // Verify it's in the database
            $check = $conn->query("SELECT id, doctor_id, status FROM consultations WHERE id = $consult_id");
            if ($check && $verify = $check->fetch_assoc()) {
                echo "\n✓ VERIFIED in database:\n";
                echo "  - ID: {$verify['id']}\n";
                echo "  - Doctor: {$verify['doctor_id']}\n";
                echo "  - Status: '{$verify['status']}'\n";
            }
        } else {
            echo "ERROR: " . $conn->error . "\n";
        }
    } else {
        echo "ERROR: No patient found\n";
    }
} else {
    echo "ERROR: Sophia Martinez not found in database\n";
}