<?php
require_once 'db.php';

$doctor_id = 10060;
$cons_id = 10008;

echo "=== Doctor Specialty Hex ===\n";
$doc = $conn->query("SELECT specialization FROM doctor_profiles WHERE user_id = $doctor_id")->fetch_assoc();
$spec = $doc['specialization'];
echo "String: '$spec'\n";
echo "Hex: " . bin2hex($spec) . "\n\n";

echo "=== Consultation Specialty Hex ===\n";
$cons = $conn->query("SELECT matched_specialty FROM consultations WHERE id = $cons_id")->fetch_assoc();
$ms = $cons['matched_specialty'];
echo "String: '$ms'\n";
echo "Hex: " . bin2hex($ms) . "\n\n";

if (strtolower(trim($spec)) === strtolower(trim($ms))) {
    echo "PHP matches them!\n";
} else {
    echo "PHP does NOT match them!\n";
}
?>
