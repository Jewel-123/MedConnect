<?php
require_once 'db.php';

echo "=== Verifying get_active_consultations for Consultation 10014 ===\n";

// Find doctor for 10014
$res = $conn->query("SELECT doctor_id FROM consultations WHERE id = 10014");
if ($row = $res->fetch_assoc()) {
    $doctor_id = $row['doctor_id'];
    echo "Consultation #10014 is assigned to Doctor #$doctor_id\n";
    
    // Test the logic
    $query = "
        (SELECT c.id, c.patient_id, u.full_name as patient_name, CAST('consultation' AS CHAR) as type
        FROM consultations c
        JOIN users u ON c.patient_id = u.id
        WHERE c.doctor_id = $doctor_id 
          AND c.status IN ('accepted', 'confirmed', 'in_progress', 'paused'))
        
        UNION ALL
        
        (SELECT a.id, a.patient_id, u.full_name as patient_name, CAST('appointment' AS CHAR) as type
        FROM appointments a
        JOIN users u ON a.patient_id = u.id
        WHERE a.doctor_id = $doctor_id 
          AND a.status IN ('confirmed', 'in_progress', 'paused'))
    ";

    $test_res = $conn->query($query);
    if ($test_res) {
        $found = false;
        while ($item = $test_res->fetch_assoc()) {
            if ($item['id'] == 10014 && $item['type'] == 'consultation') {
                $found = true;
                echo "SUCCESS: Found Consultation #10014. Patient ID: " . ($item['patient_id'] ?? 'MISSING') . "\n";
            }
        }
        if (!$found) echo "Record #10014 not found in active list. Status might not be in the active set.\n";
    } else {
        echo "Query failed: " . $conn->error . "\n";
    }
} else {
    echo "Consultation #10014 not found.\n";
}
?>
