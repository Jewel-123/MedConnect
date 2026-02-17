<?php
// Simple direct SQL approach
require_once 'db.php';

// Check if Jewel Biju consultation already exists
$existing = $conn->query("SELECT id FROM consultations WHERE patient_id = 21 AND payment_status = 'paid' ORDER BY id DESC LIMIT 1")->fetch_assoc();

if ($existing) {
    echo "Consultation already exists: ID {$existing['id']}\n";
    echo "Deleting old consultation...\n";
    $conn->query("DELETE FROM consultations WHERE id = {$existing['id']}");
}

// Create new consultation
$result = $conn->query("
    INSERT INTO consultations 
    (patient_id, consultation_fee, payment_status, status, symptoms, consultation_mode, created_at, updated_at) 
    VALUES 
    (21, 100.00, 'paid', 'pending', 'Fever, headache, and body aches for 3 days', 'chat', NOW(), NOW())
");

if ($result) {
    $id = $conn->insert_id;
    echo "✓ Created consultation #$id for Jewel Biju\n";
    echo "✓ Symptoms: Fever, headache, and body aches for 3 days\n";
    echo "✓ Status: pending (will appear in Incoming Requests)\n";
    echo "\nRefresh the doctor dashboard to see it!\n";
} else {
    echo "✗ Error: " . $conn->error . "\n";
}
?>
