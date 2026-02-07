<?php
require_once 'db.php';

// Get all doctors and their profiles
$query = "
    SELECT u.id, u.full_name, u.email, u.role,
           dp.specialization, dp.consultation_fee, dp.license_number
    FROM users u
    LEFT JOIN doctor_profiles dp ON u.id = dp.user_id
    WHERE u.role = 'doctor'
    ORDER BY u.id
    LIMIT 15
";

$result = $conn->query($query);

echo "=== DOCTOR PROFILES ===\n\n";
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']}\n";
    echo "Name: {$row['full_name']}\n";
    echo "Email: {$row['email']}\n";
    echo "Specialization: " . ($row['specialization'] ?? 'N/A') . "\n";
    echo "Consultation Fee: ₹" . ($row['consultation_fee'] ?? '0.00') . "\n";
    echo "License: " . ($row['license_number'] ?? 'N/A') . "\n";
    echo "---\n";
}

// Count total doctors
$count = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'doctor'")->fetch_assoc();
echo "\nTotal Doctors: {$count['total']}\n";