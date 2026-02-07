<?php
// Find the Sophia Martinez doctor and check for real consultations
require_once 'db.php';

echo "=== Finding Sophia Martinez ===\n\n";

$sophia = $conn->query("
    SELECT u.id, u.full_name, u.email, dp.specialization
    FROM users u
    LEFT JOIN doctor_profiles dp ON u.id = dp.user_id
    WHERE u.full_name LIKE '%Sophia%' OR u.full_name LIKE '%Martinez%'
")->fetch_assoc();

if (!$sophia) {
    echo "Sophia Martinez not found!\n";
    exit;
}

echo "Doctor: {$sophia['full_name']} (ID: {$sophia['id']})\n";
echo "Specialization: {$sophia['specialization']}\n\n";

$doctor_id = $sophia['id'];

echo "=== Consultations for Dr. Sophia Martinez ===\n";
$consultations = $conn->query("
    SELECT c.id, u.full_name as patient, c.status, c.payment_status, 
           c.consultation_fee, c.symptoms, c.created_at
    FROM consultations c
    JOIN users u ON c.patient_id = u.id
    WHERE c.doctor_id = $doctor_id
    ORDER BY c.created_at DESC
");

if ($consultations && $consultations->num_rows > 0) {
    while ($row = $consultations->fetch_assoc()) {
        echo "\nID: {$row['id']}\n";
        echo "  Patient: {$row['patient']}\n";
        echo "  Status: {$row['status']}\n";
        echo "  Payment: {$row['payment_status']}\n";
        echo "  Fee: {$row['consultation_fee']}\n";
        echo "  Symptoms: {$row['symptoms']}\n";
        echo "  Created: {$row['created_at']}\n";
    }
} else {
    echo "No consultations found for this doctor\n";
}

echo "\n=== ALL Recent Paid Consultations (Any Doctor) ===\n";
$allPaid = $conn->query("
    SELECT c.id, u.full_name as patient, c.doctor_id, d.full_name as doctor, 
           c.status, c.consultation_fee, c.created_at
    FROM consultations c
    JOIN users u ON c.patient_id = u.id
    LEFT JOIN users d ON c.doctor_id = d.id
    WHERE c.payment_status = 'paid'
    ORDER BY c.created_at DESC
    LIMIT 10
");

while ($row = $allPaid->fetch_assoc()) {
    $docName = $row['doctor'] ?: "Doctor ID: {$row['doctor_id']}";
    echo "ID:{$row['id']}, Patient:{$row['patient']}, Doctor:$docName, Status:'{$row['status']}', Fee:{$row['consultation_fee']}, Created:{$row['created_at']}\n";
}
