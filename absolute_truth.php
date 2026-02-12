<?php
include 'db.php';
echo "--- ALL ORDERS ---\n";
$res = $conn->query("SELECT * FROM prescription_orders");
while ($row = $res->fetch_assoc()) {
    echo "ID:{$row['id']} RX:{$row['prescription_id']} PAT:{$row['patient_id']} PHARM:{$row['pharmacy_id']} STATUS:[{$row['order_status']}]\n";
}
echo "--- ALL USERS (PHARM/PAT) ---\n";
$res = $conn->query("SELECT id, role, full_name FROM users WHERE role IN ('pharmacy', 'patient')");
while ($row = $res->fetch_assoc()) {
    echo "ID:{$row['id']} ROLE:{$row['role']} NAME:{$row['full_name']}\n";
}
?>
