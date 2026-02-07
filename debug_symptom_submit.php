<?php
// Test the symptom submission
session_start();
$_SESSION['user_id'] = 21; // JEWEL BIJU
$_SESSION['role'] = 'patient';

require_once 'db.php';

echo "=== Testing Symptom Submission ===\n\n";

// Simulate the submission
$_POST['action'] = 'submit_symptoms';
$input = [
    'symptoms' => 'Skin rash on my arms',
    'duration' => '3 days',
    'severity' => 'moderate',
    'consultation_mode' => 'video'
];

// Manually execute the logic from symptom_intake_api.php
try {
    require_once 'nlp_symptom_analyzer.php';
    
    $symptoms = trim($input['symptoms']);
    $duration = trim($input['duration'] ?? '');
    $severity = $input['severity'] ?? 'moderate';
    $consultationMode = $input['consultation_mode'] ?? 'video';
    $userId = $_SESSION['user_id'];
    
    echo "1. Analyzing symptoms...\n";
    $analyzer = new SymptomAnalyzer($conn);
    $analysis = $analyzer->analyze($symptoms);
    
    echo "   Matched specialty: " . ($analysis['primary_specialty'] ?? 'None') . "\n";
    echo "   Urgency: " . ($analysis['urgency_level'] ?? 'Unknown') . "\n\n";
    
    $urgencyScore = $analysis['urgency_score'];
    $urgencyLevel = $analysis['urgency_level'];
    $matchedSpecialty = $analysis['primary_specialty'];
    
    echo "2. Finding doctor for specialty: $matchedSpecialty\n";
    
    $doctorId = null;
    $consultationFee = 0;
    
    if ($matchedSpecialty) {
        $stmt = $conn->prepare("
            SELECT u.id, dp.consultation_fee
            FROM users u
            JOIN doctor_profiles dp ON u.id = dp.user_id
            WHERE dp.specialization LIKE CONCAT('%', ?, '%')
              AND u.role = 'doctor'
              AND u.status = 'approved'
            ORDER BY RAND()
            LIMIT 1
        ");
        $stmt->bind_param("s", $matchedSpecialty);
        $stmt->execute();
        $doctor = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($doctor) {
            $doctorId = $doctor['id'];
            $consultationFee = floatval($doctor['consultation_fee']);
            echo "   ✅ Found doctor ID: $doctorId, Fee: ₹$consultationFee\n\n";
        } else {
            echo "   ❌ No doctor found for specialty: $matchedSpecialty\n\n";
        }
    }
    
    echo "3. Creating consultation...\n";
    echo "   This is where the error might be!\n\n";
    
    // Check what would be inserted
    echo "SQL would insert:\n";
    echo "   patient_id: $userId\n";
    echo "   doctor_id: " . ($doctorId ?? 'NULL') . "\n";
    echo "   symptoms: $symptoms\n";
    echo "   consultation_fee: $consultationFee\n";
    echo "   payment_status: pending\n";
    echo "   status: pending\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
