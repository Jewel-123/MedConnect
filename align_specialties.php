<?php
require_once 'db.php';

$alignment_map = [
    'Dr. Emily Smith' => 'General Physician',
    'Dr. James Wilson' => 'Cardiologist',
    'Dr. Sarah Lee' => 'Pediatrician',
    'Dr. Michael Brown' => 'Neurologist',
    'Dr. Sophia Martinez' => 'Dermatologist',
    'Dr. David Chen' => 'Orthopedic Surgeon',
    'Dr. Elena Rodriguez' => 'Psychiatrist',
    'Dr. Robert Taylor' => 'Ophthalmologist',
    'Dr. Lisa Wong' => 'ENT Specialist',
    'Dr. Jennifer Adams' => 'Gynecologist',
    'Dr. Kevin Park' => 'Gastroenterologist',
    'Dr. Amanda White' => 'Endocrinologist'
];

echo "Aligning Doctor Specialties to match Home Page...\n\n";

foreach ($alignment_map as $name => $specialty) {
    // Be careful with "Dr. " prefix in DB vs Script
    // In DB, name usually includes "Dr." or not?
    // Let's strip "Dr. " for loose matching just in case, or try exact first.
    
    // The previous restoration script put 'Dr. Emily Smith' (with Dr.) for ID 14
    // But 'James Wilson' (without Dr.) for others? 
    // Let's check names in DB first.
    // Restoration script used:
    // [14, 'Dr. Emily Smith', ...]
    // [15, 'James Wilson', ...]
    
    // So for James Wilson, we need to search by 'James Wilson' but the map has 'Dr. James Wilson'.
    // We will try to match loosely.
    
    $cleanName = str_replace('Dr. ', '', $name);
    
    // Try exact match first
    $stmt = $conn->prepare("SELECT id FROM users WHERE full_name = ? OR full_name = ?");
    $stmt->bind_param("ss", $name, $cleanName);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $userId = $row['id'];
        
        // Update Profile
        $upd = $conn->prepare("UPDATE doctor_profiles SET specialization = ? WHERE user_id = ?");
        $upd->bind_param("si", $specialty, $userId);
        
        if ($upd->execute()) {
            echo "✓ Updated $name (ID: $userId) to $specialty\n";
        } else {
            echo "✗ Failed update for $name: " . $conn->error . "\n";
        }
    } else {
        echo "✗ Doctor not found in DB: $name (tried '$cleanName' too)\n";
    }
}

echo "\nAlignment Complete.\n";