<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$log_file = 'diagnostic.log';
file_put_contents($log_file, "--- Diagnostic Start ---\n");

include 'db.php';
if ($conn->connect_error) {
    file_put_contents($log_file, "Connection failed: " . $conn->connect_error . "\n", FILE_APPEND);
    die();
}
file_put_contents($log_file, "Database connected.\n", FILE_APPEND);

// Fetch a valid consultation
$res = $conn->query("SELECT id, doctor_id, patient_id FROM consultations LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    $consultation_id = $row['id'];
    $doctor_id = $row['doctor_id'];
    $patient_id = $row['patient_id'];
    file_put_contents($log_file, "Using real data: Cons=$consultation_id, Dr=$doctor_id, Pat=$patient_id\n", FILE_APPEND);
} else {
    file_put_contents($log_file, "No consultations found in DB. Cannot test FKs properly.\n", FILE_APPEND);
    // Setup dummy if needed
    $consultation_id = 111; 
    $doctor_id = 96; 
    $patient_id = 1;
}

$rating = 5;
$review_text = "Diagnostic Test Run " . date('Y-m-d H:i:s');

// Check for existing review and delete
$conn->query("DELETE FROM doctor_reviews WHERE consultation_id = $consultation_id AND patient_id = $patient_id");

// Perform INSERT
$stmt = $conn->prepare("
    INSERT INTO doctor_reviews 
    (doctor_id, patient_id, consultation_id, rating, review_text)
    VALUES (?, ?, ?, ?, ?)
");

if (!$stmt) {
    file_put_contents($log_file, "Prepare failed: " . $conn->error . "\n", FILE_APPEND);
    die();
}

$stmt->bind_param("iiiis", $doctor_id, $patient_id, $consultation_id, $rating, $review_text);

try {
    if ($stmt->execute()) {
        file_put_contents($log_file, "SUCCESS: Review inserted.\n", FILE_APPEND);
    } else {
        file_put_contents($log_file, "FAILURE: " . $stmt->error . "\n", FILE_APPEND);
    }
} catch (mysqli_sql_exception $e) {
    file_put_contents($log_file, "EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
}

$stmt->close();
$conn->close();
file_put_contents($log_file, "--- Diagnostic End ---\n", FILE_APPEND);
echo "Diagnostic completed. Check $log_file\n";
?>
