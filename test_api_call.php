<?php
// Simulate session
session_id('test_session');
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'patient';
session_write_close();

// Prepare input
$data = json_encode([
    'action' => 'submit_feedback',
    'consultation_id' => 56, // ID from previous log (even if doctor_id is null, API should validation error, not crash)
    'doctor_id' => 1,
    'rating' => 5,
    'review_text' => 'Test'
]);

// Use Curl to hit localhost
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/medconnect/patient_api.php");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: PHPSESSID=test_session'
]);
// Capture headers too to check for warnings
curl_setopt($ch, CURLOPT_HEADER, 1);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpcode\n";
echo "Response Length: " . strlen($response) . "\n";
echo "Raw Response:\n----------------\n";
echo $response;
echo "\n----------------\n";
?>
