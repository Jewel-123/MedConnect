<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';

$output = "--- Doctor Profiles ---\n";
// Manually fetch doctor profiles
$ids = [25, 29];
foreach ($ids as $id) {
    $output .= "Data for ID $id:\n";
    $sql = "SELECT u.full_name, dp.specialization FROM users u LEFT JOIN doctor_profiles dp ON u.id = dp.user_id WHERE u.id = $id";
    $res = $conn->query($sql);
    if ($res) {
        $output .= print_r($res->fetch_assoc(), true) . "\n";
    } else {
        $output .= "Query failed: " . $conn->error . "\n";
    }
}

$output .= "\n--- Unassigned Consultations (Last 5) ---\n";
$sql = "SELECT id, doctor_id, matched_specialty, symptoms, created_at FROM consultations WHERE doctor_id IS NULL OR doctor_id = 0 ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($sql);
if ($result) {
    while($row = $result->fetch_assoc()) {
        $output .= print_r($row, true) . "\n";
    }
} else {
    $output .= "Query failed: " . $conn->error . "\n";
}

file_put_contents('debug_specialty_output.txt', $output);
echo "Done.";
?>
