<?php
/**
 * Test script to verify doctor consultation workflow
 * This tests the strict doctor-specific routing
 */

require_once 'db.php';

echo "=== TESTING DOCTOR-SPECIFIC CONSULTATION ROUTING ===\n\n";

// 1. Check if there are consultations in the system
$totalConsults = $conn->query("SELECT COUNT(*) as count FROM consultations")->fetch_assoc()['count'];
echo "Total consultations in system: $totalConsults\n\n";

// 2. Show distribution of consultations by doctor
echo "=== Consultations by Doctor ===\n";
$byDoctor = $conn->query("
    SELECT 
        COALESCE(u.full_name, 'Unassigned') as doctor_name,
        c.doctor_id,
        COUNT(*) as count,
        SUM(CASE WHEN c.status = 'pending' AND c.payment_status = 'paid' THEN 1 ELSE 0 END) as incoming_requests,
        SUM(CASE WHEN c.status = 'in_progress' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN c.status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM consultations c
    LEFT JOIN users u ON c.doctor_id = u.id
    GROUP BY c.doctor_id, u.full_name
    ORDER BY c.doctor_id
");

while ($row = $byDoctor->fetch_assoc()) {
    echo "Doctor: {$row['doctor_name']} (ID: " . ($row['doctor_id'] ?? 'NULL') . ")\n";
    echo "  Total: {$row['count']}\n";
    echo "  Incoming Requests: {$row['incoming_requests']}\n";
    echo "  Active: {$row['active']}\n";
    echo "  Completed: {$row['completed']}\n";
    echo "---\n";
}

// 3. Show sample incoming request query for a specific doctor
echo "\n=== SAMPLE INCOMING REQUESTS QUERY ===\n";
echo "Testing for Doctor ID 25 (Emily Smith):\n\n";

$doctor_id = 25;
$requests = $conn->query("
    SELECT c.id, c.patient_id, u.full_name as patient_name,
           c.doctor_id, c.status, c.payment_status,
           c.consultation_fee, c.created_at
    FROM consultations c
    JOIN users u ON c.patient_id = u.id
    WHERE c.doctor_id = $doctor_id
      AND c.status = 'pending'
      AND c.payment_status = 'paid'
    ORDER BY c.created_at DESC
    LIMIT 5
");

if ($requests->num_rows > 0) {
    while ($row = $requests->fetch_assoc()) {
        echo "Consultation #{$row['id']}\n";
        echo "  Patient: {$row['patient_name']}\n";
        echo "  Doctor ID: {$row['doctor_id']}\n";
        echo "  Status: {$row['status']}\n";
        echo "  Payment: {$row['payment_status']}\n";
        echo "  Fee: ₹{$row['consultation_fee']}\n";
        echo "  Created: {$row['created_at']}\n";
        echo "---\n";
    }
} else {
    echo "No incoming requests for Doctor ID 25\n";
}

// 4. Check for any NULL doctor_id assignments
echo "\n=== CONSULTATIONS WITH NULL DOCTOR_ID ===\n";
$nullDoctors = $conn->query("
    SELECT COUNT(*) as count, 
           SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid
    FROM consultations 
    WHERE doctor_id IS NULL
")->fetch_assoc();

echo "Total with NULL doctor_id: {$nullDoctors['count']}\n";
echo "Paid consultations with NULL doctor_id: {$nullDoctors['paid']}\n";

if ($nullDoctors['count'] > 0) {
    echo "\n⚠️  WARNING: Found consultations with NULL doctor_id.\n";
    echo "These will NOT appear in any doctor's dashboard.\n";
    echo "Action needed: Assign these to specific doctors or cancel them.\n";
}

echo "\n=== TEST COMPLETE ===\n";