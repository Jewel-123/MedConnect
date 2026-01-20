<?php
require_once 'db.php';

echo "<h2>Pharmacy Database Schema Enhancement</h2>";

try {
    // Read and execute the schema enhancement SQL
    $sql = file_get_contents('pharmacy_schema_enhancement.sql');
    
    // Split by semicolons and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        if ($conn->query($statement)) {
            $successCount++;
        } else {
            $errors[] = "Error: " . $conn->error . " | Statement: " . substr($statement, 0, 100);
        }
    }
    
    echo "<div style='background: #d1fae5; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #065f46;'>✅ Schema Enhancement Complete</h3>";
    echo "<p>Successfully executed $successCount SQL statements</p>";
    echo "</div>";
    
    if (!empty($errors)) {
        echo "<div style='background: #fee2e2; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3 style='color: #991b1b;'>⚠️ Some Errors Occurred</h3>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    // Verify pharmacy account
    echo "<hr><h3>Pharmacy Account Verification</h3>";
    
    $stmt = $conn->prepare("
        SELECT u.id, u.email, u.full_name, u.status, u.role,
               pp.pharmacy_name, pp.license_number, pp.delivery_available,
               pp.sms_notifications_enabled, pp.email_notifications_enabled
        FROM users u
        LEFT JOIN pharmacy_profiles pp ON u.id = pp.user_id
        WHERE u.email = ? AND u.role = 'pharmacy'
    ");
    
    $email = 'pharmacy@medconnect.com';
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $pharmacy = $result->fetch_assoc();
        
        echo "<div style='background: #dbeafe; padding: 20px; border-radius: 8px;'>";
        echo "<h4 style='color: #1e40af;'>✅ Pharmacy Account Found</h4>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin-top: 10px;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td><strong>ID</strong></td><td>{$pharmacy['id']}</td></tr>";
        echo "<tr><td><strong>Email</strong></td><td>{$pharmacy['email']}</td></tr>";
        echo "<tr><td><strong>Password</strong></td><td>pharmacy123 (unchanged)</td></tr>";
        echo "<tr><td><strong>Full Name</strong></td><td>{$pharmacy['full_name']}</td></tr>";
        echo "<tr><td><strong>Pharmacy Name</strong></td><td>" . ($pharmacy['pharmacy_name'] ?: 'Not Set') . "</td></tr>";
        echo "<tr><td><strong>Status</strong></td><td>{$pharmacy['status']}</td></tr>";
        echo "<tr><td><strong>SMS Notifications</strong></td><td>" . ($pharmacy['sms_notifications_enabled'] ? 'Enabled' : 'Disabled') . "</td></tr>";
        echo "<tr><td><strong>Email Notifications</strong></td><td>" . ($pharmacy['email_notifications_enabled'] ? 'Enabled' : 'Disabled') . "</td></tr>";
        echo "</table>";
        echo "</div>";
        
        // Check if profile exists, if not create one
        if (!$pharmacy['pharmacy_name']) {
            echo "<p style='color: #f59e0b;'>⚠️ Pharmacy profile missing. Creating default profile...</p>";
            
            $userId = $pharmacy['id'];
            $pharmacyName = 'MedConnect Central Pharmacy';
            $licenseNumber = 'PH' . rand(10000, 99999);
            $address = '123 Healthcare Avenue, Medical District';
            $phone = '1234567890';
            
            $stmt2 = $conn->prepare("
                INSERT INTO pharmacy_profiles 
                (user_id, pharmacy_name, license_number, owner_name, address, phone_number, operating_hours, delivery_available, verification_status)
                VALUES (?, ?, ?, ?, ?, ?, '24/7', TRUE, 'verified')
            ");
            $stmt2->bind_param("isssss", $userId, $pharmacyName, $licenseNumber, $pharmacy['full_name'], $address, $phone);
            
            if ($stmt2->execute()) {
                echo "<p style='color: #10b981;'>✅ Pharmacy profile created successfully!</p>";
            }
        }
        
    } else {
        echo "<div style='background: #fee2e2; padding: 20px; border-radius: 8px;'>";
        echo "<h4 style='color: #991b1b;'>❌ Pharmacy Account Not Found</h4>";
        echo "<p>Creating pharmacy account: pharmacy@medconnect.com</p>";
        echo "</div>";
        
        // Create pharmacy account
        $password = password_hash('pharmacy123', PASSWORD_BCRYPT);
        $fullName = 'MedConnect Pharmacy';
        
        $stmt = $conn->prepare("
            INSERT INTO users (email, password, full_name, role, status, created_at)
            VALUES (?, ?, ?, 'pharmacy', 'approved', NOW())
        ");
        $stmt->bind_param("sss", $email, $password, $fullName);
        
        if ($stmt->execute()) {
            $userId = $stmt->insert_id;
            
            // Create pharmacy profile
            $pharmacyName = 'MedConnect Central Pharmacy';
            $licenseNumber = 'PH' . rand(10000, 99999);
            $address = '123 Healthcare Avenue, Medical District';
            $phone = '1234567890';
            
            $stmt2 = $conn->prepare("
                INSERT INTO pharmacy_profiles 
                (user_id, pharmacy_name, license_number, owner_name, address, phone_number, operating_hours, delivery_available, verification_status)
                VALUES (?, ?, ?, ?, ?, ?, '24/7', TRUE, 'verified')
            ");
            $stmt2->bind_param("isssss", $userId, $pharmacyName, $licenseNumber, $fullName, $address, $phone);
            $stmt2->execute();
            
            echo "<div style='background: #d1fae5; padding: 20px; border-radius: 8px; margin-top: 20px;'>";
            echo "<h4 style='color: #065f46;'>✅ Pharmacy Account Created</h4>";
            echo "<p><strong>Email:</strong> pharmacy@medconnect.com</p>";
            echo "<p><strong>Password:</strong> pharmacy123</p>";
            echo "<p><strong>Pharmacy Name:</strong> $pharmacyName</p>";
            echo "</div>";
        }
    }
    
    // Verify new tables
    echo "<hr><h3>Database Tables Verification</h3>";
    
    $tables = [
        'pharmacy_notifications',
        'pharmacy_inventory_alerts',
        'pharmacy_analytics',
        'pharmacy_settings'
    ];
    
    echo "<ul>";
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "<li style='color: #10b981;'>✅ Table <strong>$table</strong> exists</li>";
        } else {
            echo "<li style='color: #ef4444;'>❌ Table <strong>$table</strong> missing</li>";
        }
    }
    echo "</ul>";
    
    echo "<hr>";
    echo "<h3>Quick Links</h3>";
    echo "<ul>";
    echo "<li><a href='login.php' style='color: #667eea; font-weight: bold;'>Login Page</a></li>";
    echo "<li><a href='pharmacy_dashboard.php' style='color: #667eea; font-weight: bold;'>Pharmacy Dashboard</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<div style='background: #fee2e2; padding: 20px; border-radius: 8px;'>";
    echo "<h3 style='color: #991b1b;'>Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
