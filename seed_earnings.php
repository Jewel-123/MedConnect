<?php
require_once 'db.php';

// Get a doctor ID
$docRes = $conn->query("SELECT id FROM users WHERE role = 'doctor' LIMIT 1");
if ($docRes->num_rows === 0) die("No doctor found");
$doctor_id = $docRes->fetch_assoc()['id'];

// Get a completed consultation (or create a dummy one if needed, but lets assume one exists or we just link to a ID 1)
// For safety, let's insert a dummy earnings record directly
$conn->query("
    INSERT INTO doctor_earnings 
    (doctor_id, consultation_id, gross_amount, platform_commission_percent, platform_commission_amount, net_amount, payment_status, created_at)
    VALUES 
    ($doctor_id, 1, 50.00, 10.00, 5.00, 45.00, 'pending', NOW()),
    ($doctor_id, 2, 75.00, 10.00, 7.50, 67.50, 'paid', DATE_SUB(NOW(), INTERVAL 2 DAY))
");

echo "Seeded earnings for Doctor ID: $doctor_id";
?>
