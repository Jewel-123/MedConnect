<?php
require_once 'db.php';

echo "=== Doctor 10062 User Record ===\n";
$res = $conn->query("SELECT * FROM users WHERE id = 10062");
var_export($res->fetch_assoc());
echo "\n\n";

echo "=== Doctor 10062 Profile ===\n";
$res = $conn->query("SELECT * FROM doctor_profiles WHERE user_id = 10062");
var_export($res->fetch_assoc());
echo "\n\n";

echo "=== Appointment 70 ===\n";
$res = $conn->query("SELECT * FROM appointments WHERE id = 70");
var_export($res->fetch_assoc());
echo "\n\n";

echo "=== All Doctor IDs in Appointments Table ===\n";
$res = $conn->query("SELECT DISTINCT doctor_id FROM appointments");
while($row = $res->fetch_assoc()) {
    var_export($row); echo "\n";
}
?>
