<?php
/**
 * Test prescription save endpoint
 */
session_start();
require_once 'db.php';

// Simulate doctor login
$_SESSION['user_id'] = 1; // Replace with actual doctor ID
$_SESSION['role'] = 'doctor';

header('Content-Type: application/json');

$testData = [
    'action' => 'save_prescription',
    'consultation_id' => 1, // Replace with actual consultation ID
    'patient_id' => 2, // Replace with actual patient ID
    'diagnosis' => 'Test diagnosis',
    'medicines' => [
        [
            'name' => 'Test Medicine',
            'dosage' => '500mg',
            'frequency' => '2 times daily',
            'duration' => '7 days',
            'instructions' => 'Take with food',
            'quantity' => 1
        ]
    ],
    'tests' => [],
    'follow_up_date' => '2026-01-20',
    'notes_for_patient' => 'Test notes for patient',
    'notes_for_pharmacy' => 'Test notes for pharmacy'
];

// Test the API
$ch = curl_init('http://localhost/MedConnect/doctor_api.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . session_name() . '=' . session_id()
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n\n";
echo "Response:\n";
echo $response;