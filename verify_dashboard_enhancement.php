<?php
// Verification script for doctor dashboard enhancements
include 'db.php';

echo "=== Doctor Dashboard Enhancement Verification ===\n\n";

// 1. Check appointments table schema
echo "1. Checking appointments table schema...\n";
$result = $conn->query("SHOW COLUMNS FROM appointments LIKE 'payment_transaction_id'");
if ($result && $result->num_rows > 0) {
    echo "   ✓ payment_transaction_id column exists\n";
} else {
    echo "   ✗ payment_transaction_id column MISSING\n";
}

// 2. Check for paid appointments
echo "\n2. Checking for paid appointments in the system...\n";
$result = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE payment_status = 'paid'");
$count = $result->fetch_assoc()['count'];
echo "   Found $count paid appointment(s)\n";

// 3. Check for confirmed appointments
echo "\n3. Checking for confirmed appointments...\n";
$result = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'confirmed' AND payment_status = 'paid'");
$count = $result->fetch_assoc()['count'];
echo "   Found $count confirmed appointment(s)\n";

// 4. Check API file exists
echo "\n4. Verifying API files...\n";
if (file_exists('doctor_api.php')) {
    echo "   ✓ doctor_api.php exists\n";
} else {
    echo "   ✗ doctor_api.php MISSING\n";
}

if (file_exists('doctor_dashboard_v3.js')) {
    echo "   ✓ doctor_dashboard_v3.js exists\n";
} else {
    echo "   ✗ doctor_dashboard_v3.js MISSING\n";
}

echo "\n=== Verification Complete ===\n";
$conn->close();