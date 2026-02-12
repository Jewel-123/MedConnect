<?php
require_once 'db.php';
$tables = ['prescription_orders', 'prescriptions_v2'];
foreach ($tables as $table) {
    $res = $conn->query("SHOW COLUMNS FROM $table LIKE 'paid_at'");
    echo "$table has paid_at: " . ($res->num_rows > 0 ? "YES" : "NO") . "\n";
    
    // Also check for payment_status and other related columns
    $res = $conn->query("SHOW COLUMNS FROM $table LIKE 'payment_status'");
    echo "$table has payment_status: " . ($res->num_rows > 0 ? "YES" : "NO") . "\n";
}
?>
