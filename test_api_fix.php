<?php
require_once 'db.php';
header('Content-Type: text/plain');

// Simulate a patient session if possible, or just run the query manually
$userId = 1; // Assuming a test user ID
$query = "
    SELECT p.*, 
           po.order_number,
           po.id as order_id,
           po.order_status,
           po.payment_status,
           po.review_submitted,
           po.total_amount as order_amount
    FROM prescriptions_v2 p
    LEFT JOIN prescription_orders po ON p.id = po.prescription_id
    LIMIT 1
";

$res = $conn->query($query);
if ($res) {
    echo "✅ Query successful! po.review_submitted exists.\n";
    print_r($res->fetch_assoc());
} else {
    echo "❌ Query failed: " . $conn->error . "\n";
}
?>
