<?php
require_once 'db.php';

echo "=== USER: Emily Smith ===\n";
$res = $conn->query("SELECT * FROM users WHERE full_name LIKE '%Emily Smith%'");
$emily = $res->fetch_assoc();
if ($emily) {
    print_r($emily);
    $did = $emily['id'];
    $res2 = $conn->query("SELECT * FROM doctor_profiles WHERE user_id = $did");
    print_r($res2->fetch_assoc());
} else {
    echo "Emily Smith not found.\n";
}

echo "\n=== RECENT CONSULTATIONS (Last 3) ===\n";
$res = $conn->query("SELECT c.*, u.full_name as patient_name FROM consultations c JOIN users u ON c.patient_id = u.id ORDER BY c.id DESC LIMIT 3");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\n=== RECENT APPOINTMENTS (Last 3) ===\n";
$res = $conn->query("SELECT a.*, u.full_name as patient_name FROM appointments a JOIN users u ON a.patient_id = u.id ORDER BY a.id DESC LIMIT 3");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
