<?php
require_once 'db.php';
$output = "";
$tables = ['prescription_orders', 'prescriptions_v2'];
foreach ($tables as $table) {
    $res = $conn->query("SHOW COLUMNS FROM $table LIKE 'paid_at'");
    $output .= "$table has paid_at: " . ($res->num_rows > 0 ? "YES" : "NO") . "\n";
    
    $res = $conn->query("SHOW COLUMNS FROM $table LIKE 'payment_status'");
    $output .= "$table has payment_status: " . ($res->num_rows > 0 ? "YES" : "NO") . "\n";
}
file_put_contents('schema_check_output.txt', $output);
echo "Output written to schema_check_output.txt\n";
?>
