<?php
require_once 'db.php';
$id = 10014;
$res = $conn->query("SELECT * FROM consultations WHERE id = $id");
$row = $res->fetch_assoc();
echo "--- Consultation $id ---\n";
print_r($row);

if ($row) {
    $p_id = $row['patient_id'];
    $user = $conn->query("SELECT * FROM users WHERE id = $p_id")->fetch_assoc();
    echo "\n--- Patient User $p_id ---\n";
    print_r($user);
}
?>
