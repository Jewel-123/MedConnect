<?php
session_start();
require_once 'db.php';

echo "CURRENT SESSION INFO:\n";
echo "====================\n";
if (isset($_SESSION['user_id'])) {
    echo "User ID: " . $_SESSION['user_id'] . "\n";
    echo "Role: " . ($_SESSION['role'] ?? 'not set') . "\n";
    echo "Name: " . ($_SESSION['admin_name'] ?? 'not set') . "\n\n";
    
    $doctor_id = $_SESSION['user_id'];
    
    // Get doctor details
    $res = $conn->query("SELECT u.id, u.full_name, u.email, d.specialty FROM users u LEFT JOIN doctor_profiles d ON u.id = d.user_id WHERE u.id = $doctor_id");
    if ($res && $row = $res->fetch_assoc()) {
        echo "Full Name: {$row['full_name']}\n";
        echo "Email: {$row['email']}\n";
        echo "Specialty: " . ($row['specialty'] ?? 'Not set') . "\n\n";
    }
    
    // Create 2 consultations for THIS doctor
    $patientRes = $conn->query("SELECT id FROM users WHERE role = 'patient' LIMIT 1");
    if ($patientRes && $patient = $patientRes->fetch_assoc()) {
        $patient_id = $patient['id'];
        
        // Consultation 1: Emergency
        $sql1 = "INSERT INTO consultations 
                (patient_id, doctor_id, symptoms, severity, urgency_level, urgency_score, 
                 consultation_mode, status, matched_specialty, duration, created_at) 
                VALUES 
                ($patient_id, $doctor_id, 'Severe chest pain radiating to left arm, difficulty breathing', 
                 'high', 'emergency', 95, 'video', 'pending', 'Cardiology', 'Less than 24 hours', NOW())";
        
        if ($conn->query($sql1)) {
            echo "✓ Created EMERGENCY consultation ID: " . $conn->insert_id . "\n";
        }
        
        // Consultation 2: Urgent
        $sql2 = "INSERT INTO consultations 
                (patient_id, doctor_id, symptoms, severity, urgency_level, urgency_score, 
                 consultation_mode, status, matched_specialty, duration, created_at) 
                VALUES 
                ($patient_id, $doctor_id, 'High fever 103°F, severe headache, body aches for 2 days', 
                 'medium', 'urgent', 75, 'audio', 'pending', 'General Medicine', '1-2 days', NOW())";
        
        if ($conn->query($sql2)) {
            echo "✓ Created URGENT consultation ID: " . $conn->insert_id . "\n";
        }
        
        // Verification
        $check = $conn->query("
            SELECT COUNT(*) as count 
            FROM consultations 
            WHERE doctor_id = $doctor_id AND status IN ('pending', 'reassigned')
        ");
        $count = $check->fetch_assoc()['count'];
        echo "\n✓ TOTAL pending consultations for this doctor: $count\n";
        
    } else {
        echo "ERROR: No patient found\n";
    }
    
} else {
    echo "✗ NOT LOGGED IN\n";
    echo "You need to log in to the doctor dashboard first!\n";
}