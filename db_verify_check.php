<?php
require_once 'db.php';

$doctor_id = 25;
$specialization = 'General Physician';

echo "=== Stats for Dr. Emily Smith (ID $doctor_id) ===\n";

// Consultations
$cons_res = $conn->query("
    SELECT COUNT(*) as count FROM consultations 
    WHERE (doctor_id = $doctor_id OR (doctor_id IS NULL AND matched_specialty = '$specialization'))
      AND status IN ('pending', 'assigned')
      AND payment_status = 'paid'
");
$cons_count = $cons_res->fetch_assoc()['count'];
echo "Paid Pending Consultations: $cons_count\n";

// Appointments
$app_res = $conn->query("
    SELECT COUNT(*) as count FROM appointments
    WHERE doctor_id = $doctor_id
      AND status = 'pending'
      AND payment_status = 'paid'
");
$app_count = $app_res->fetch_assoc()['count'];
echo "Paid Pending Appointments: $app_count\n";

$total = (int)$cons_count + (int)$app_count;
echo "Total Unified: $total\n";

echo "\n=== Details of Paid Appointments ===\n";
$app_details = $conn->query("SELECT id, patient_id, created_at, scheduled_date FROM appointments WHERE doctor_id = $doctor_id AND payment_status = 'paid' AND status = 'pending'");
while($row = $app_details->fetch_assoc()) {
    print_r($row);
}
?>
