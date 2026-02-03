<?php
/**
 * Quick Login Fix Script
 * This script will:
 * 1. Check database connection
 * 2. Ensure users table exists
 * 3. Create a test patient account if needed
 */

require_once 'db.php';

echo "\n========================================\n";
echo "MedConnect Login Fix Script\n";
echo "========================================\n\n";

// Step 1: Check connection
echo "Step 1: Checking database connection...\n";
if ($conn->connect_error) {
    echo "❌ ERROR: Cannot connect to database\n";
    echo "   Message: " . $conn->connect_error . "\n\n";
    echo "SOLUTION:\n";
    echo "1. Start XAMPP Control Panel\n";
    echo "2. Click 'Start' next to MySQL\n";
    echo "3. Run this script again\n\n";
    exit;
}
echo "✅ Database connection successful\n\n";

// Step 2: Check if users table exists
echo "Step 2: Checking if users table exists...\n";
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows == 0) {
    echo "❌ ERROR: Users table does not exist\n\n";
    echo "SOLUTION:\n";
    echo "1. Open phpMyAdmin (http://localhost/phpmyadmin)\n";
    echo "2. Select 'medconnect' database\n";
    echo "3. Import the file: medconnect.sql or consolidated_database_setup.sql\n\n";
    exit;
}
echo "✅ Users table exists\n\n";

// Step 3: Check for existing users
echo "Step 3: Checking for existing users...\n";
$count = $conn->query("SELECT COUNT(*) as total FROM users");
$total = $count->fetch_assoc()['total'];
echo "Found $total user(s) in database\n\n";

// Step 4: Create test patient if no users exist
if ($total == 0) {
    echo "Step 4: Creating test patient account...\n";
    $testEmail = "patient@test.com";
    $testPassword = password_hash("password", PASSWORD_DEFAULT);
    $testName = "Test Patient";
    
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, status, is_verified) VALUES (?, ?, ?, 'patient', 'approved', 1)");
    $stmt->bind_param("sss", $testName, $testEmail, $testPassword);
    
    if ($stmt->execute()) {
        $userId = $stmt->insert_id;
        
        // Create patient profile
        $stmt2 = $conn->prepare("INSERT INTO patient_profiles (user_id, gender) VALUES (?, 'other')");
        $stmt2->bind_param("i", $userId);
        $stmt2->execute();
        
        echo "✅ Test patient created successfully!\n\n";
        echo "========================================\n";
        echo "LOGIN CREDENTIALS:\n";
        echo "========================================\n";
        echo "Email: patient@test.com\n";
        echo "Password: password\n";
        echo "========================================\n\n";
    } else {
        echo "❌ Failed to create test patient: " . $conn->error . "\n\n";
    }
} else {
    // List existing users
    echo "Step 4: Listing existing users...\n\n";
    echo "========================================\n";
    echo "EXISTING USERS:\n";
    echo "========================================\n";
    
    $users = $conn->query("SELECT id, full_name, email, role, status, is_verified FROM users LIMIT 10");
    while ($user = $users->fetch_assoc()) {
        $verified = $user['is_verified'] ? '✅' : '❌';
        echo sprintf(
            "%s %s (%s)\n   Email: %s\n   Role: %s | Status: %s\n\n",
            $verified,
            $user['full_name'],
            $user['role'],
            $user['email'],
            $user['role'],
            $user['status']
        );
    }
    echo "========================================\n\n";
    
    // Check if there's an approved patient
    $approved = $conn->query("SELECT email FROM users WHERE role='patient' AND status='approved' LIMIT 1");
    if ($approved->num_rows > 0) {
        $patient = $approved->fetch_assoc();
        echo "✅ You can login with: " . $patient['email'] . "\n";
        echo "   (Use the password you set during signup)\n\n";
    } else {
        echo "⚠️  No approved patient accounts found\n";
        echo "   You may need to approve accounts or create a new one\n\n";
    }
}

echo "========================================\n";
echo "LOGIN FIX COMPLETE\n";
echo "========================================\n";
echo "Next steps:\n";
echo "1. Go to: http://localhost/medconnect/login.php\n";
echo "2. Use the credentials shown above\n";
echo "3. If login still fails, check browser console for errors\n";
echo "========================================\n\n";

$conn->close();
?>
