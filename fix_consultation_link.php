<?php
session_start();
require_once 'db.php';

echo "<h1>Fixing Pharmacy Dashboard Issue</h1>";

// The problem: prescriptions don't have consultation_id
// Solution: Either add consultation_id OR modify the pharmacy query to not require it

echo "<h2>Option 1: Add Dummy Consultation IDs</h2>";

// First, check if consultations exist
$consultations = $conn->query("SELECT COUNT(*) as count FROM consultations")->fetch_assoc();
echo "Total consultations in database: {$consultations['count']}<br><br>";

if ($consultations['count'] > 0) {
    // Link prescriptions to consultations
    $result = $conn->query("
        UPDATE prescriptions_v2 p
        LEFT JOIN consultations c ON p.patient_id = c.patient_id AND p.doctor_id = c.doctor_id
        SET p.consultation_id = c.id
        WHERE p.consultation_id IS NULL AND c.id IS NOT NULL
        LIMIT 10
    ");
    
    if ($result) {
        echo "✅ Linked prescriptions to consultations: {$conn->affected_rows} updated<br><br>";
    }
}

// For prescriptions without matching consultations, create dummy ones
$orphanPrescriptions = $conn->query("
    SELECT id, patient_id, doctor_id, diagnosis 
    FROM prescriptions_v2 
    WHERE consultation_id IS NULL 
    LIMIT 10
");

$created = 0;
while ($rx = $orphanPrescriptions->fetch_assoc()) {
    // Create a dummy consultation
    $symptoms = $rx['diagnosis'] ?? 'General checkup';
    $conn->query("
        INSERT INTO consultations (patient_id, doctor_id, symptoms, diagnosis, status, created_at)
        VALUES ({$rx['patient_id']}, {$rx['doctor_id']}, '$symptoms', '$symptoms', 'completed', NOW())
    ");
    
    $consultationId = $conn->insert_id;
    
    // Link prescription to consultation
    $conn->query("
        UPDATE prescriptions_v2 
        SET consultation_id = $consultationId 
        WHERE id = {$rx['id']}
    ");
    
    $created++;
}

echo "✅ Created $created dummy consultations and linked to prescriptions<br><br>";

// Verify the fix
echo "<h2>Verification</h2>";
$check = $conn->query("
    SELECT COUNT(*) as count 
    FROM prescriptions_v2 
    WHERE consultation_id IS NULL
")->fetch_assoc();

echo "Prescriptions without consultation_id: {$check['count']}<br><br>";

if ($check['count'] == 0) {
    echo "✅ <strong>All prescriptions now have consultation_id!</strong><br><br>";
    echo "<a href='pharmacy_dashboard.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Pharmacy Dashboard</a><br><br>";
    echo "<small>Remember to clear cache (Ctrl+F5) or hard refresh!</small>";
} else {
    echo "⚠️ Some prescriptions still missing consultation_id<br>";
}

// Show what pharmacy should see now
$pharmacyId = $conn->query("SELECT id FROM users WHERE email = 'central.pharmacy@medconnect.com'")->fetch_assoc()['id'];

echo "<h2>Pending Prescriptions for Pharmacy</h2>";
$pending = $conn->query("
    SELECT p.id, p.prescription_number, p.status, pat.full_name as patient_name
    FROM prescriptions_v2 p
    JOIN consultations c ON p.consultation_id = c.id
    JOIN users pat ON p.patient_id = pat.id
    WHERE p.pharmacy_id = $pharmacyId AND p.status = 'sent_to_pharmacy'
");

if ($pending->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Prescription #</th><th>Patient</th><th>Status</th></tr>";
    while ($row = $pending->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['prescription_number']}</td>";
        echo "<td>{$row['patient_name']}</td>";
        echo "<td><strong>{$row['status']}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No pending prescriptions found.";
}
?>
