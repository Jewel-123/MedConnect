<?php
require_once 'db.php';

// Get pharmacy user ID
$email = 'pharmacy@medconnect.com';
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $userId = $user['id'];
    
    // Check if profile exists
    $checkProfile = $conn->prepare("SELECT id FROM pharmacy_profiles WHERE user_id = ?");
    $checkProfile->bind_param("i", $userId);
    $checkProfile->execute();
    $profileExists = $checkProfile->get_result()->num_rows > 0;
    
    if ($profileExists) {
        echo "✅ Pharmacy profile already exists!\n";
    } else {
        echo "Creating pharmacy profile...\n";
        
        // Create pharmacy profile
        $pharmacyName = 'MedConnect Central Pharmacy';
        $licenseNumber = 'PH' . rand(10000, 99999);
        $ownerName = 'MedConnect Pharmacy';
        $address = '123 Healthcare Avenue, Medical District';
        $phone = '1234567890';
        $operatingHours = '24/7';
        $deliveryAvailable = 1;
        
        $stmt = $conn->prepare("
            INSERT INTO pharmacy_profiles 
            (user_id, pharmacy_name, license_number, owner_name, address, phone_number, operating_hours, delivery_available, verification_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'verified')
        ");
        
        $stmt->bind_param("issssssi", $userId, $pharmacyName, $licenseNumber, $ownerName, $address, $phone, $operatingHours, $deliveryAvailable);
        
        if ($stmt->execute()) {
            echo "✅ Pharmacy profile created successfully!\n";
            echo "\nProfile Details:\n";
            echo "User ID: $userId\n";
            echo "Pharmacy Name: $pharmacyName\n";
            echo "License: $licenseNumber\n";
            echo "Address: $address\n";
            echo "Operating Hours: $operatingHours\n";
            echo "Delivery Available: Yes\n";
        } else {
            echo "❌ Failed to create profile: " . $conn->error . "\n";
        }
    }
} else {
    echo "❌ Pharmacy user not found!\n";
}
?>
