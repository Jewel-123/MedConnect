<?php
require_once 'db.php';

echo "--- Searching for Emily Smith ---\n";
$emily_res = $conn->query("SELECT id, name, role FROM users WHERE name LIKE '%Emily Smith%'");
$emily = $emily_res->fetch_assoc();
if ($emily) {
    print_r($emily);
    $emily_id = $emily['id'];
    
    // Get Emily's specialization
    $spec_res = $conn->query("SELECT specialization FROM doctor_profiles WHERE user_id = $emily_id");
    $spec = $spec_res ? $spec_res->fetch_assoc() : null;
    echo "Specialization: " . ($spec['specialization'] ?? 'NONE') . "\n";
} else {
    echo "Emily Smith not found in users table.\n";
}

echo "\n--- Recent consultations matching 'fever' ---\n";
$query = "
    SELECT c.*, u.full_name as patient_name
    FROM consultations c
    JOIN users u ON c.patient_id = u.id
    WHERE c.symptoms LIKE '%fever%' OR c.reason LIKE '%fever%'
    ORDER BY c.created_at DESC LIMIT 5
";
$res = $conn->query($query);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "ID: {$row['id']} | Patient: {$row['patient_name']} | Doctor ID: " . ($row['doctor_id'] ?? 'NULL') . " | Status: {$row['status']} | Payment: {$row['payment_status']} | Specialty: " . ($row['matched_specialty'] ?? 'NULL') . " | Created: {$row['created_at']}\n";
        print_r($row);
    }
} else {
    echo "Error in consultation query: " . $conn->error . "\n";
}

echo "\n--- Recent consultations (ALL) ---\n";
$res = $conn->query("SELECT c.*, u.full_name as patient_name FROM consultations c JOIN users u ON c.patient_id = u.id ORDER BY c.created_at DESC LIMIT 5");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "ID: {$row['id']} | Patient: {$row['patient_name']} | Doctor ID: " . ($row['doctor_id'] ?? 'NULL') . " | Status: {$row['status']} | Payment: {$row['payment_status']} | Created: {$row['created_at']}\n";
    }
}
?>
