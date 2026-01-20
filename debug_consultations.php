<?php
require_once 'db.php';
$res = $conn->query("SELECT id, patient_id, doctor_id, status, matched_specialty, created_at FROM consultations ORDER BY created_at DESC LIMIT 10");
echo "RECENT CONSULTATIONS:\n";
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

$res = $conn->query("SELECT id, full_name, email, role FROM users WHERE email LIKE '%doctor%' OR full_name LIKE '%Sophia%'");
echo "\nDOCTOR INFO:\n";
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

$res = $conn->query("SELECT * FROM doctor_profiles");
echo "\nDOCTOR PROFILES:\n";
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
