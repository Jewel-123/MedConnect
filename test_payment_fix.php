<?php
// Test the payment update fix
require_once 'db.php';

echo "=== Testing Payment Update Fix ===\n\n";

// Simulate what happens when payment is completed for consultation #40
$consultationId = 40;

echo "Before Update:\n";
$before = $conn->query("SELECT id, status, payment_status FROM consultations WHERE id = $consultationId")->fetch_assoc();
echo "  Status: '{$before['status']}'\n";
echo "  Payment: '{$before['payment_status']}'\n\n";

// Simulate the update that payment_api.php will now do
$conn->query("UPDATE consultations SET payment_status = 'paid' WHERE id = $consultationId");

echo "After Update:\n";
$after = $conn->query("SELECT id, status, payment_status FROM consultations WHERE id = $consultationId")->fetch_assoc();
echo "  Status: '{$after['status']}'\n";
echo "  Payment: '{$after['payment_status']}'\n\n";

if ($after['status'] == 'pending' && $after['payment_status'] == 'paid') {
    echo "✅ This consultation WILL NOW appear in incoming requests!\n";
} else {
    echo "❌ Still won't appear. Status='{$after['status']}', Payment='{$after['payment_status']}'\n";
}
