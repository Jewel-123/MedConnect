<?php
require_once 'db.php';

$doctors = [
    [14, 'Dr. Emily Smith', 'dr.emily@medconnect.com', 'Pediatrician', 'Child specialist'],
    [15, 'James Wilson', 'wilson@gmail.com', 'Cardiologist', 'Heart specialist'],
    [16, 'Sarah Lee', 'lee@gmail.com', 'Dermatologist', 'Skin specialist'],
    [17, 'Michael Brown', 'brown@gmail.com', 'Neurologist', 'Brain specialist'],
    [18, 'Sophia Martinez', 'martinez@gmail.com', 'Dermatologist', 'Skin specialist/Surgeon'],
    [19, 'David Chen', 'chen@gmail.com', 'General Physician', 'General health'],
    [20, 'Elena Rodriguez', 'rodriguez@gmail.com', 'Gynecologist', 'Women health'],
    [21, 'Robert Taylor', 'taylor@gmail.com', 'Orthopedist', 'Bone specialist'],
    [22, 'Lisa Wong', 'wong@gmail.com', 'Psychiatrist', 'Mental health'],
    [23, 'Jennifer Adams', 'adams@gmail.com', 'ENT Specialist', 'Ear Nose Throat'],
    [24, 'Kevin Park', 'park@gmail.com', 'Ophthalmologist', 'Eye specialist'],
    [25, 'Amanda White', 'white@gmail.com', 'Dentist', 'Dental care']
];

echo "Restoring 12 doctors...\n";

foreach ($doctors as $doc) {
    $id = $doc[0];
    $name = $doc[1];
    $email = $doc[2];
    $spec = $doc[3];
    $bio = $doc[4];
    $pass = password_hash('doctor123', PASSWORD_DEFAULT);

    // 1. Insert/Update User (Force ID)
    // Note: We cannot force ID easily if auto_increment is higher, but we can try.
    // If ID exists, update. If not, insert with ID.
    
    $check = $conn->query("SELECT id FROM users WHERE id=$id");
    if ($check->num_rows > 0) {
        $sql = "UPDATE users SET full_name='$name', email='$email', role='doctor', status='approved' WHERE id=$id";
    } else {
        $sql = "INSERT INTO users (id, full_name, email, password, role, status) VALUES ($id, '$name', '$email', '$pass', 'doctor', 'approved')";
    }
    
    if ($conn->query($sql)) {
        echo "✓ User $name (ID: $id) restored.\n";
        
        // 2. Insert/Update Profile
        $checkProf = $conn->query("SELECT id FROM doctor_profiles WHERE user_id=$id");
        if ($checkProf->num_rows > 0) {
            $sqlProf = "UPDATE doctor_profiles SET specialization='$spec', bio='$bio' WHERE user_id=$id";
        } else {
            $sqlProf = "INSERT INTO doctor_profiles (user_id, specialization, license_number, consultation_fee, bio, languages) 
                        VALUES ($id, '$spec', 'LIC-$id', 500.00, '$bio', 'English, Spanish')";
        }
        $conn->query($sqlProf);
    } else {
        echo "✗ Error restoring $name: " . $conn->error . "\n";
    }
}

echo "Restoration complete.\n";