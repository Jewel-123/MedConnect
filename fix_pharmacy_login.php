<?php
require_once 'db.php';

echo "<h2>Pharmacy Account Check & Fix</h2>";

$email = 'pharmacy@medconnect.com';
$password = 'pharmacy123';

// Check if account exists
$stmt = $conn->prepare("SELECT id, email, password, role, status FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    echo "<p>✅ Account found!</p>";
    echo "<p>Email: {$user['email']}</p>";
    echo "<p>Role: {$user['role']}</p>";
    echo "<p>Status: {$user['status']}</p>";
    
    // Check password
    if (password_verify($password, $user['password'])) {
        echo "<p style='color: green;'>✅ Password is correct!</p>";
    } else {
        echo "<p style='color: red;'>❌ Password doesn't match. Resetting...</p>";
        
        // Reset password
        $newHash = password_hash($password, PASSWORD_BCRYPT);
        $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateStmt->bind_param("si", $newHash, $user['id']);
        
        if ($updateStmt->execute()) {
            echo "<p style='color: green;'>✅ Password reset successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to reset password</p>";
        }
    }
    
    // Ensure status is approved
    if ($user['status'] !== 'approved') {
        echo "<p style='color: orange;'>⚠️ Status is '{$user['status']}'. Setting to 'approved'...</p>";
        
        $updateStmt = $conn->prepare("UPDATE users SET status = 'approved' WHERE id = ?");
        $updateStmt->bind_param("i", $user['id']);
        
        if ($updateStmt->execute()) {
            echo "<p style='color: green;'>✅ Status updated to 'approved'!</p>";
        }
    }
    
    // Check pharmacy profile
    $profileStmt = $conn->prepare("SELECT * FROM pharmacy_profiles WHERE user_id = ?");
    $profileStmt->bind_param("i", $user['id']);
    $profileStmt->execute();
    $profileResult = $profileStmt->get_result();
    
    if ($profileResult->num_rows === 0) {
        echo "<p style='color: orange;'>⚠️ No pharmacy profile found. Creating...</p>";
        
        $pharmacyName = 'MedConnect Central Pharmacy';
        $licenseNumber = 'PH' . rand(10000, 99999);
        $ownerName = $user['email'];
        $address = '123 Healthcare Avenue, Medical District';
        $phone = '1234567890';
        
        $insertStmt = $conn->prepare("
            INSERT INTO pharmacy_profiles 
            (user_id, pharmacy_name, license_number, owner_name, address, phone_number, operating_hours, delivery_available, verification_status)
            VALUES (?, ?, ?, ?, ?, ?, '24/7', TRUE, 'verified')
        ");
        $insertStmt->bind_param("isssss", $user['id'], $pharmacyName, $licenseNumber, $ownerName, $address, $phone);
        
        if ($insertStmt->execute()) {
            echo "<p style='color: green;'>✅ Pharmacy profile created!</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ Pharmacy profile exists!</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ Account not found. Creating...</p>";
    
    // Create account
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $fullName = 'MedConnect Pharmacy';
    
    $insertStmt = $conn->prepare("
        INSERT INTO users (email, password, full_name, role, status, created_at)
        VALUES (?, ?, ?, 'pharmacy', 'approved', NOW())
    ");
    $insertStmt->bind_param("sss", $email, $passwordHash, $fullName);
    
    if ($insertStmt->execute()) {
        $userId = $insertStmt->insert_id;
        echo "<p style='color: green;'>✅ Account created! ID: $userId</p>";
        
        // Create pharmacy profile
        $pharmacyName = 'MedConnect Central Pharmacy';
        $licenseNumber = 'PH' . rand(10000, 99999);
        $address = '123 Healthcare Avenue, Medical District';
        $phone = '1234567890';
        
        $profileStmt = $conn->prepare("
            INSERT INTO pharmacy_profiles 
            (user_id, pharmacy_name, license_number, owner_name, address, phone_number, operating_hours, delivery_available, verification_status)
            VALUES (?, ?, ?, ?, ?, ?, '24/7', TRUE, 'verified')
        ");
        $profileStmt->bind_param("isssss", $userId, $pharmacyName, $licenseNumber, $fullName, $address, $phone);
        
        if ($profileStmt->execute()) {
            echo "<p style='color: green;'>✅ Pharmacy profile created!</p>";
        }
    }
}

echo "<hr>";
echo "<h3>Login Credentials:</h3>";
echo "<p><strong>Email:</strong> pharmacy@medconnect.com</p>";
echo "<p><strong>Password:</strong> pharmacy123</p>";
echo "<p><a href='login.php' style='color: #667eea; font-weight: bold;'>Go to Login Page</a></p>";