<?php
require_once 'db.php';
header('Content-Type: text/plain');

$fp = fopen('diagnosis_log.txt', 'w');

function log_msg($msg) {
    global $fp;
    fwrite($fp, $msg . "\n");
    echo $msg . "\n";
}

log_msg("Starting valid diagnosis...");

// 1. Fetch a valid completed consultation
$sql = "SELECT id, doctor_id, patient_id FROM consultations WHERE status = 'completed' AND doctor_id IS NOT NULL AND doctor_id > 0 LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    log_msg("WARNING: No valid completed consultations found (with doctor_id). Trying 'in_progress'...");
    $sql = "SELECT id, doctor_id, patient_id FROM consultations WHERE doctor_id IS NOT NULL AND doctor_id > 0 LIMIT 1";
    $result = $conn->query($sql);
}

$consult = $result->fetch_assoc();

if (!$consult) {
    log_msg("ERROR: No consultations involving a doctor found at all.");
    fclose($fp);
    exit;
}

$consultation_id = $consult['id'];
$doctor_id = $consult['doctor_id'];
$patient_id = $consult['patient_id'];

log_msg("Found Valid Consultation: ID=$consultation_id, Doctor=$doctor_id, Patient=$patient_id");

// 2. Check for existing feedback
$check = $conn->query("SELECT id FROM doctor_reviews WHERE consultation_id = $consultation_id");
if ($check->num_rows > 0) {
    log_msg("Feedback exists. Deleting...");
    $conn->query("DELETE FROM doctor_reviews WHERE consultation_id = $consultation_id");
}

// 3. Attempt Insert via Prepared Statement
$rating = 5;
$review_text = "Diagnosis Test - Valid Doctor";

$stmt = $conn->prepare("INSERT INTO doctor_reviews (doctor_id, patient_id, consultation_id, rating, review_text) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    log_msg("Prepare failed: " . $conn->error);
    fclose($fp);
    exit;
}

$stmt->bind_param("iiiis", $doctor_id, $patient_id, $consultation_id, $rating, $review_text);

if ($stmt->execute()) {
    log_msg("SUCCESS: Insert worked with valid doctor ID!");
    // Clean up
    $stmt_id = $stmt->insert_id;
    $conn->query("DELETE FROM doctor_reviews WHERE id = $stmt_id");
    log_msg("Cleaned up test review.");
} else {
    log_msg("FAILURE: Insert failed: " . $stmt->error);
}

fclose($fp);
?>
