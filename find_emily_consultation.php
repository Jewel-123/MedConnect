<?php
// Find Emily Smith consultation and check why it's not showing
require_once 'db.php';

echo "=== Searching for Emily Smith Consultation ===\n\n";

// Search for Emily Smith
$emily = $conn->query("
    SELECT u.id, u.full_name, u.email 
    FROM users u 
    WHERE u.full_name LIKE '%Emily%' OR u.full_name LIKE '%Smith%'
");

if ($emily && $emily->num_rows > 0) {
    echo "Found users matching 'Emily Smith':\n";
    while ($user = $emily->fetch_assoc()) {
        echo "  - ID: {$user['id']}, Name: {$user['full_name']}, Email: {$user['email']}\n";
        
        // Check consultations for this user
        $consults = $conn->query("
            SELECT id, doctor_id, status, payment_status, consultation_fee, created_at 
            FROM consultations 
            WHERE patient_id = {$user['id']}
            ORDER BY created_at DESC
        ");
        
        if ($consults && $consults->num_rows > 0) {
            echo "    Consultations:\n";
            while ($c = $consults->fetch_assoc()) {
                echo "      - ID: {$c['id']}, Doctor: {$c['doctor_id']}, Status: '{$c['status']}', Payment: '{$c['payment_status']}', Fee: {$c['consultation_fee']}, Created: {$c['created_at']}\n";
            }
        } else {
            echo "    No consultations found\n";
        }
    }
} else {
    echo "No users found matching 'Emily Smith'\n";
}

echo "\n=== All Recent Consultations (Last 10) ===\n";
$recent = $conn->query("
    SELECT c.id, c.patient_id, u.full_name, c.doctor_id, c.status, c.payment_status, c.consultation_fee, c.created_at
    FROM consultations c
    LEFT JOIN users u ON c.patient_id = u.id
    ORDER BY c.created_at DESC
    LIMIT 10
");

while ($row = $recent->fetch_assoc()) {
    $statusVal = $row['status'] ?: 'EMPTY';
    $paymentVal = $row['payment_status'] ?: 'EMPTY';
    echo "ID: {$row['id']}, Patient: {$row['full_name']}, Doctor: {$row['doctor_id']}, Status: '$statusVal', Payment: '$paymentVal', Fee: {$row['consultation_fee']}, Created: {$row['created_at']}\n";
}

echo "\n=== Count by Status ===\n";
$counts = $conn->query("
    SELECT 
        status,
        payment_status,
        COUNT(*) as count
    FROM consultations
    WHERE doctor_id = 25
    GROUP BY status, payment_status
");

while ($row = $counts->fetch_assoc()) {
    $s = $row['status'] ?: 'EMPTY';
    $p = $row['payment_status'] ?: 'EMPTY';
    echo "Status: '$s', Payment: '$p' => Count: {$row['count']}\n";
}
