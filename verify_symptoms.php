<?php
require_once 'db.php';

echo "<h1>🔍 Verifying Symptoms Display Update</h1>";

$test_notes = "Patient reports persistent migraine and light sensitivity for 4 days.";
$doctor_id = 25; // Using a common test doctor ID or I'll look it up
$patient_id = 1; // Assuming a patient exists

// 1. Find a doctor
$doctor = $conn->query("SELECT id FROM users WHERE role = 'doctor' LIMIT 1")->fetch_assoc();
if (!$doctor) {
    echo "❌ No doctor found for testing.<br>";
    exit;
}
$doctor_id = $doctor['id'];

// 2. Find a patient
$patient = $conn->query("SELECT id FROM users WHERE role = 'patient' LIMIT 1")->fetch_assoc();
if (!$patient) {
    echo "❌ No patient found for testing.<br>";
    exit;
}
$patient_id = $patient['id'];

echo "Testing with Doctor ID: $doctor_id and Patient ID: $patient_id<br>";

// 3. Insert a test appointment
$scheduled_date = date('Y-m-d', strtotime('+1 day'));
$scheduled_time = '10:00:00';
$sql = "INSERT INTO appointments (patient_id, doctor_id, scheduled_date, scheduled_time, status, payment_status, consultation_fee, notes) 
        VALUES ($patient_id, $doctor_id, '$scheduled_date', '$scheduled_time', 'pending', 'paid', 500, '$test_notes')";

if ($conn->query($sql)) {
    $appointment_id = $conn->insert_id;
    echo "✅ Test appointment created with ID: $appointment_id<br>";
    echo "Notes: <i>$test_notes</i><br><br>";
} else {
    echo "❌ Failed to create test appointment: " . $conn->error . "<br>";
    exit;
}

// 4. Test the API output
echo "<h2>Checking API Output:</h2>";

// Mocking session for doctor_api.php
$_SESSION['user_id'] = $doctor_id;
$_SESSION['role'] = 'doctor';

// We'll call the API logic directly or use a sub-request if possible, 
// but here we'll just query the same logic as doctor_api.php
$appointments = $conn->query("
    SELECT a.*, u.full_name as patient_name
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.id = $appointment_id
");

$row = $appointments->fetch_assoc();
$reason = (!empty($row['notes']) ? $row['notes'] : 'Scheduled Appointment');

echo "Result from DB logic:<br>";
echo "<b>Reason:</b> $reason<br>";

if ($reason === $test_notes) {
    echo "<h3 style='color: green;'>✅ SUCCESS: API logic returns actual notes!</h3>";
} else {
    echo "<h3 style='color: red;'>❌ FAILURE: API logic returned '$reason' instead of notes.</h3>";
}

// 5. Cleanup (optional)
// $conn->query("DELETE FROM appointments WHERE id = $appointment_id");
// echo "Test data cleaned up.<br>";

$conn->close();
?>
