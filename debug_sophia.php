<?php
require_once 'db.php';

$output = "";
$doctor_id = 29;

// 1. Payments for Doctor Sophia
$output .= "--- Payments for Doctor Sophia (ID $doctor_id) ---\n";
$sql = "SELECT * FROM payment_transactions WHERE doctor_id = $doctor_id ORDER BY created_at DESC";
$result = $conn->query($sql);
$payments = $result->fetch_all(MYSQLI_ASSOC);
$output .= print_r($payments, true) . "\n";

// 2. Unassigned Consultations
$output .= "--- Unassigned Consultations (Doctor ID NULL or 0) ---\n";
$sql = "SELECT c.id, c.patient_id, u.full_name as patient_name, c.status, c.payment_status, c.created_at 
        FROM consultations c 
        JOIN users u ON c.patient_id = u.id 
        WHERE c.doctor_id IS NULL OR c.doctor_id = 0
        ORDER BY c.created_at DESC";
$result = $conn->query($sql);
$unassigned = $result->fetch_all(MYSQLI_ASSOC);
$output .= print_r($unassigned, true) . "\n";

// 3. Appointments for Doctor Sophia
$output .= "--- Appointments for Doctor Sophia ---\n";
$sql = "SELECT * FROM appointments WHERE doctor_id = $doctor_id ORDER BY created_at DESC";
$result = $conn->query($sql);
$appointments = $result->fetch_all(MYSQLI_ASSOC);
$output .= print_r($appointments, true) . "\n";

file_put_contents('debug_output.txt', $output);
echo "Debug output written to debug_output.txt";
?>
