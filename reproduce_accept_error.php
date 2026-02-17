<?php
// reproduce_accept_error.php
// Reproduce the issue where unassigned consultations cannot be accepted.

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

$db_file = __DIR__ . '/db.php';
if (!file_exists($db_file)) {
    die("db.php NOT FOUND at $db_file\n");
}
require_once $db_file;

if (!isset($conn)) {
    die("\$conn is NOT SET after requiring db.php\n");
}
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}
echo "Database connected.\n";

// simulating Doctor Emily Smith (ID: 25)
$doctor_id = 25;
$_SESSION['user_id'] = $doctor_id;
$_SESSION['role'] = 'doctor';
$_SESSION['full_name'] = 'Emily Smith';

echo "=== Reproducing Accept Consultation Error ===\n";
echo "Doctor ID: $doctor_id\n";

// Get doctor's specialty
$res = $conn->query("SELECT specialization FROM doctor_profiles WHERE user_id = $doctor_id");
if ($res && $row = $res->fetch_assoc()) {
    $specialty = $row['specialization'];
    echo "Doctor Specialty: $specialty\n";
} else {
    // Fallback if doctor or profile doesn't exist
    $specialty = 'Cardiology';
    echo "Could not find doctor profile for ID $doctor_id. Using fallback: $specialty\n";
}

// Create a dummy unassigned consultation for this specialty
$patient_email = 'test_patient_' . time() . '@example.com';
$conn->query("INSERT INTO users (full_name, email, password, role) VALUES ('Test Patient', '$patient_email', 'password', 'patient')");
$patient_id = $conn->insert_id;

$symptoms = "Test symptoms for reproduction";
$txn_id = "TXN_" . time();

$sql = "INSERT INTO consultations 
    (patient_id, doctor_id, consultation_fee, payment_status, status, symptoms, consultation_mode, payment_transaction_id, created_at, updated_at, matched_specialty) 
    VALUES 
    ($patient_id, NULL, 100.00, 'paid', 'pending', '$symptoms', 'chat', '$txn_id', NOW(), NOW(), '$specialty')";

if (!$conn->query($sql)) {
    die("Failed to create test consultation: " . $conn->error . "\n");
}
$consultation_id = $conn->insert_id;
echo "Created UNASSIGNED consultation #$consultation_id matching specialty '$specialty'\n";

// Attempt to accept it
echo "Attempting to accept consultation #$consultation_id...\n";

$_POST['action'] = 'accept_consultation';
$_POST['consultation_id'] = $consultation_id;

// Capture output
ob_start();
try {
    include 'doctor_api.php';
} catch (Exception $e) {
    echo "Caught Exception: " . $e->getMessage();
}
$response = ob_get_clean();

echo "\nResponse from doctor_api.php:\n";
echo $response . "\n";

// Check if it was accepted
$check = $conn->query("SELECT doctor_id, status FROM consultations WHERE id = $consultation_id")->fetch_assoc();
echo "Final Consultation State:\n";
echo "Doctor ID: " . ($check['doctor_id'] ?? 'NULL') . "\n";
echo "Status: " . $check['status'] . "\n";

if ($check['doctor_id'] == $doctor_id && $check['status'] == 'accepted') {
    echo "SUCCESS: Consultation was accepted.\n";
} else {
    echo "FAILURE: Consultation was NOT accepted.\n";
}
?>
