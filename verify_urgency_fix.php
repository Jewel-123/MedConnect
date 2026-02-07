<?php
require_once 'db.php';
require_once 'nlp_symptom_analyzer.php';

echo "=== VERIFYING URGENCY LEVEL FIX ===\n\n";

// 1. Create a dummy patient if not exists
$conn->query("INSERT IGNORE INTO users (id, full_name, email, password, role, is_verified, status) 
              VALUES (999, 'Test Patient', 'test_patient@example.com', 'password', 'patient', 1, 'approved')");

$_SESSION['user_id'] = 999;

// 2. Simulate symptom submission logic from symptom_intake_api.php
$symptoms = "I have chest pain and shortness of breath";
$duration = "2 hours";
$severity = "severe";

echo "Step 1: Analyzing symptoms: '$symptoms'\n";
$analyzer = new SymptomAnalyzer($conn);
$analysis = $analyzer->analyze($symptoms);

$urgencyScore = $analysis['urgency_score'];
$urgencyLevel = $analysis['urgency_level'];
$matchedSpecialty = $analysis['primary_specialty'];

echo "Analysis result: Urgency=$urgencyLevel, Score=$urgencyScore, Specialty=$matchedSpecialty\n";

echo "Step 2: Attempting to insert into consultations...\n";
$stmt = $conn->prepare("
    INSERT INTO consultations (
        patient_id, symptoms, duration, severity, age, gender,
        existing_conditions, input_method, urgency_score, urgency_level,
        matched_specialty, consultation_mode, language_preference, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
");

$userId = 999;
$age = 30;
$gender = 'male';
$existingConditions = 'none';
$inputMethod = 'text';
$consultationMode = 'video';
$languagePref = 'English';

$stmt->bind_param(
    "issssississss",
    $userId, $symptoms, $duration, $severity, $age, $gender,
    $existingConditions, $inputMethod, $urgencyScore, $urgencyLevel,
    $matchedSpecialty, $consultationMode, $languagePref
);

if ($stmt->execute()) {
    $consultationId = $stmt->insert_id;
    echo "✓ SUCCESS: Consultation #$consultationId created successfully!\n";
    
    // Cleanup
    $conn->query("DELETE FROM consultations WHERE id = $consultationId");
    echo "✓ Cleanup: Removed test consultation.\n";
} else {
    echo "✗ FAILURE: " . $stmt->error . "\n";
}

$stmt->close();
$conn->query("DELETE FROM users WHERE id = 999");
echo "\nVerification Complete.\n";