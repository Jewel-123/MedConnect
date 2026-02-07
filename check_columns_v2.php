<?php
include 'db.php';
function check_column($conn, $table, $column) {
    $res = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
    if ($res && $res->num_rows > 0) {
        echo "✅ $table HAS $column\n";
    } else {
        echo "❌ $table MISSING $column\n";
    }
}

check_column($conn, 'appointments', 'payment_transaction_id');
check_column($conn, 'prescription_orders', 'payment_transaction_id');
$conn->close();