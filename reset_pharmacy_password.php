<?php
require_once 'db.php';

echo "<h2>Reset Pharmacy Password</h2>";

$email = 'pharmacy@medconnect.com';
$newPassword = 'pharmacy123';

// Check if account exists
$stmt = $conn->prepare("SELECT id, full_name, status FROM users WHERE email = ? AND role = 'pharmacy'");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    
    // Update password
    $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $updateStmt->bind_param("ss", $hashedPassword, $email);
    
    if ($updateStmt->execute()) {
        echo "<div style='background: #d1fae5; padding: 25px; border-radius: 12px; border: 2px solid #10b981; margin: 20px 0;'>";
        echo "<h3 style='color: #065f46; margin-bottom: 15px;'>✅ Password Reset Successful!</h3>";
        echo "<div style='background: white; padding: 20px; border-radius: 8px; margin-top: 15px;'>";
        echo "<p><strong>📧 Email:</strong> <span style='color: #667eea; font-size: 18px;'>$email</span></p>";
        echo "<p><strong>🔑 Password:</strong> <span style='color: #667eea; font-size: 18px;'>$newPassword</span></p>";
        echo "<p><strong>👤 Name:</strong> {$user['full_name']}</p>";
        echo "<p><strong>✓ Status:</strong> {$user['status']}</p>";
        echo "</div>";
        echo "</div>";
        
        if ($user['status'] !== 'approved') {
            // Auto-approve if not approved
            $conn->query("UPDATE users SET status = 'approved' WHERE email = '$email'");
            echo "<div style='background: #fef3c7; padding: 15px; border-radius: 8px; border: 2px solid #f59e0b; margin: 20px 0;'>";
            echo "<p>⚠️ Account was not approved. <strong>Auto-approved now!</strong></p>";
            echo "</div>";
        }
        
        echo "<div style='background: #f8fafc; padding: 20px; border-radius: 12px; margin: 20px 0;'>";
        echo "<h3 style='color: #1e293b;'>🚀 How to Login:</h3>";
        echo "<ol style='line-height: 2;'>";
        echo "<li>Open: <a href='login.php' style='color: #667eea; font-weight: bold;'>http://localhost/MedConnect/login.php</a></li>";
        echo "<li>Enter Email: <strong style='color: #667eea;'>$email</strong></li>";
        echo "<li>Enter Password: <strong style='color: #667eea;'>$newPassword</strong></li>";
        echo "<li>Click Login</li>";
        echo "<li>Go to Pharmacy Dashboard: <a href='pharmacy_dashboard.php' style='color: #667eea; font-weight: bold;'>Click Here</a></li>";
        echo "</ol>";
        echo "</div>";
        
        // Check for pharmacy profile
        $profileCheck = $conn->query("SELECT * FROM pharmacy_profiles WHERE user_id = {$user['id']}");
        if ($profileCheck->num_rows === 0) {
            echo "<div style='background: #fef3c7; padding: 15px; border-radius: 8px; border: 2px solid #f59e0b; margin: 20px 0;'>";
            echo "<p>⚠️ No pharmacy profile found. Creating one now...</p>";
            echo "</div>";
            
            // Create pharmacy profile
            $pharmacyName = 'MedConnect Pharmacy';
            $licenseNumber = 'PH' . rand(10000, 99999);
            $address = '123 Medical Street, City Center';
            $phone = '9876543210';
            
            $stmt2 = $conn->prepare("
                INSERT INTO pharmacy_profiles (user_id, pharmacy_name, license_number, address, phone_number, operating_hours, delivery_available)
                VALUES (?, ?, ?, ?, ?, '9:00 AM - 9:00 PM', TRUE)
            ");
            $stmt2->bind_param("issss", $user['id'], $pharmacyName, $licenseNumber, $address, $phone);
            
            if ($stmt2->execute()) {
                echo "<div style='background: #d1fae5; padding: 15px; border-radius: 8px; border: 2px solid #10b981;'>";
                echo "<p>✅ Pharmacy profile created!</p>";
                echo "<p><strong>Pharmacy Name:</strong> $pharmacyName</p>";
                echo "<p><strong>License:</strong> $licenseNumber</p>";
                echo "</div>";
            }
        } else {
            $profile = $profileCheck->fetch_assoc();
            echo "<div style='background: #d1fae5; padding: 15px; border-radius: 8px; border: 2px solid #10b981; margin: 20px 0;'>";
            echo "<p>✅ Pharmacy profile exists:</p>";
            echo "<p><strong>Pharmacy Name:</strong> {$profile['pharmacy_name']}</p>";
            echo "<p><strong>License:</strong> {$profile['license_number']}</p>";
            echo "</div>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Error updating password: " . $conn->error . "</p>";
    }
} else {
    echo "<div style='background: #fee2e2; padding: 20px; border-radius: 12px; border: 2px solid #ef4444;'>";
    echo "<p style='color: #991b1b;'>❌ Pharmacy account with email <strong>$email</strong> not found!</p>";
    echo "<p>Available pharmacy accounts:</p>";
    
    $allPharmacies = $conn->query("SELECT email, full_name FROM users WHERE role = 'pharmacy'");
    if ($allPharmacies->num_rows > 0) {
        echo "<ul>";
        while ($pharm = $allPharmacies->fetch_assoc()) {
            echo "<li>{$pharm['email']} - {$pharm['full_name']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No pharmacy accounts found in database.</p>";
    }
    echo "</div>";
}
?>

<style>
    body { font-family: Arial, sans-serif; padding: 30px; background: #f1f5f9; }
    h2 { color: #1e293b; }
</style>