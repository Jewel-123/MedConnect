<?php
// Find and delete all test consultations
require_once 'db.php';

echo "=== Finding Test Consultations ===\n\n";

// Find test consultations (symptoms containing "Test" or "test")
$tests = $conn->query("
    SELECT c.id, c.patient_id, u.full_name, c.doctor_id, c.symptoms, c.status, c.payment_status
    FROM consultations c
    JOIN users u ON c.patient_id = u.id
    WHERE c.symptoms LIKE '%Test%' OR c.symptoms LIKE '%test%'
    ORDER BY c.created_at DESC
");

if ($tests && $tests->num_rows > 0) {
    echo "Found {$tests->num_rows} test consultation(s):\n";
    $ids = [];
    while ($row = $tests->fetch_assoc()) {
        echo "  - ID:{$row['id']}, Patient:{$row['full_name']}, Doctor:{$row['doctor_id']}, Status:{$row['status']}, Symptoms: " . substr($row['symptoms'], 0, 50) . "...\n";
        $ids[] = $row['id'];
    }
    
    if (!empty($ids)) {
        $idList = implode(',', $ids);
        
        // Delete related earnings first
        $conn->query("DELETE FROM doctor_earnings WHERE consultation_id IN ($idList)");
        echo "\n  Deleted related earnings records\n";
        
        // Delete consultations
        $result = $conn->query("DELETE FROM consultations WHERE id IN ($idList)");
        if ($result) {
            echo "  ✅ Deleted {$conn->affected_rows} test consultation(s)\n";
        } else {
            echo "  ❌ Error deleting: " . $conn->error . "\n";
        }
    }
} else {
    echo "No test consultations found\n";
}

echo "\n=== Remaining Consultations ===\n";
$remaining = $conn->query("
    SELECT c.id, u.full_name as patient, c.doctor_id, c.status, c.payment_status, 
           c.consultation_fee, LEFT(c.symptoms, 60) as symptoms_preview, c.created_at
    FROM consultations c
    JOIN users u ON c.patient_id = u.id
    ORDER BY c.created_at DESC
    LIMIT 15
");

while ($row = $remaining->fetch_assoc()) {
    $docId = $row['doctor_id'] ?: 'NULL';
    echo "ID:{$row['id']}, Patient:{$row['patient']}, Doc:$docId, Status:'{$row['status']}', Pay:'{$row['payment_status']}', Fee:{$row['consultation_fee']}, Created:{$row['created_at']}\n";
    echo "  Symptoms: {$row['symptoms_preview']}\n";
}