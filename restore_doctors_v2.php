<?php
require_once 'db.php';

$doctors = [
    [14, 'Dr. Emily Smith', 'Pediatrician', 'Child specialist'],
    [15, 'James Wilson', 'Cardiologist', 'Heart specialist'],
    [16, 'Sarah Lee', 'Dermatologist', 'Skin specialist'],
    [17, 'Michael Brown', 'Neurologist', 'Brain specialist'],
    [18, 'Sophia Martinez', 'Dermatologist', 'Skin specialist/Surgeon'],
    [19, 'David Chen', 'General Physician', 'General health'],
    [20, 'Elena Rodriguez', 'Gynecologist', 'Women health'],
    [21, 'Robert Taylor', 'Orthopedist', 'Bone specialist'],
    [22, 'Lisa Wong', 'Psychiatrist', 'Mental health'],
    [23, 'Jennifer Adams', 'ENT Specialist', 'Ear Nose Throat'],
    [24, 'Kevin Park', 'Ophthalmologist', 'Eye specialist'],
    [25, 'Amanda White', 'Dentist', 'Dental care']
];

echo "Restoring 12 doctors with new email format (lastname@gmail.com) and password '123456'...\n";

foreach ($doctors as $doc) {
    $id = $doc[0];
    $name = $doc[1];
    $spec = $doc[2];
    $bio = $doc[3];
    
    // Extract last name for email
    $parts = explode(' ', $name);
    $lastName = end($parts);
    $email = strtolower($lastName) . '@gmail.com';
    
    // Password hash
    $pass = password_hash('123456', PASSWORD_DEFAULT);

    // 1. Insert/Update User (Force ID)
    $check = $conn->query("SELECT id FROM users WHERE id=$id");
    if ($check->num_rows > 0) {
        $sql = "UPDATE users SET full_name='$name', email='$email', password='$pass', role='doctor', status='approved' WHERE id=$id";
    } else {
        $sql = "INSERT INTO users (id, full_name, email, password, role, status) VALUES ($id, '$name', '$email', '$pass', 'doctor', 'approved')";
    }
    
    if ($conn->query($sql)) {
        echo "✓ User $name (ID: $id) restored/updated. Email: $email\n";
        
        // 2. Insert/Update Profile
        $checkProf = $conn->query("SELECT id FROM doctor_profiles WHERE user_id=$id");
        if ($checkProf->num_rows > 0) {
            $sqlProf = "UPDATE doctor_profiles SET specialization='$spec', bio='$bio' WHERE user_id=$id";
        } else {
            $sqlProf = "INSERT INTO doctor_profiles (user_id, specialization, license_number, consultation_fee, bio, languages) 
                        VALUES ($id, '$spec', 'LIC-$id', 500.00, '$bio', 'English, Spanish')";
        }
        
        if ($conn->query($sqlProf)) {
             echo "  - Profile updated.\n";
        } else {
             echo "  - Error updating profile: " . $conn->error . "\n";
        }

    } else {
        echo "✗ Error restoring $name: " . $conn->error . "\n";
    }
}

echo "Restoration complete.\n";