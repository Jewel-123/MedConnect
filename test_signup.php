<?php
// Enhanced debugging script for sign-up issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Sign-Up Debug Test</h2>";
echo "<pre>";

include 'db.php';

// Test 1: Database connection
echo "1. Testing database connection...\n";
if ($conn->connect_error) {
    echo "❌ Connection failed: " . $conn->connect_error . "\n";
    exit;
}
echo "✅ Database connected successfully\n\n";

// Test 2: Check if users table exists
echo "2. Checking if 'users' table exists...\n";
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows == 0) {
    echo "❌ Users table does NOT exist!\n";
    echo "   Run setup_db.php or verify_db.php first\n";
    exit;
}
echo "✅ Users table exists\n\n";

// Test 3: Manually insert test user
echo "3. Testing manual insert...\n";
$testEmail = "test_" . time() . "@test.com";
$testName = "Test User";
$testPassword = password_hash("testpass123", PASSWORD_DEFAULT);
$testRole = "patient";

$sql = "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo "❌ Prepare failed: " . $conn->error . "\n";
    exit;
}

$stmt->bind_param("ssss", $testName, $testEmail, $testPassword, $testRole);

if ($stmt->execute()) {
    echo "✅ Test user inserted successfully!\n";
    echo "   Email: $testEmail\n";
    echo "   Name: $testName\n";
    echo "   ID: " . $stmt->insert_id . "\n\n";
} else {
    echo "❌ Insert failed: " . $stmt->error . "\n";
    exit;
}

// Test 4: Verify the insert
echo "4. Verifying insert...\n";
$check = $conn->query("SELECT * FROM users WHERE email = '$testEmail'");
if ($check->num_rows > 0) {
    $user = $check->fetch_assoc();
    echo "✅ User found in database:\n";
    echo "   ID: {$user['id']}\n";
    echo "   Name: {$user['full_name']}\n";
    echo "   Email: {$user['email']}\n";
    echo "   Role: {$user['role']}\n\n";
} else {
    echo "❌ User NOT found after insert!\n";
}

// Test 5: Current user count
echo "5. Current users in database:\n";
$count = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
echo "   Total users: $count\n\n";

// Test 6: List all users
echo "6. All users:\n";
echo "   ID | Name | Email | Role\n";
echo "   ---|------|-------|-----\n";
$users = $conn->query("SELECT id, full_name, email, role FROM users");
while ($u = $users->fetch_assoc()) {
    echo "   {$u['id']} | {$u['full_name']} | {$u['email']} | {$u['role']}\n";
}

echo "\n========================================\n";
echo "If manual insert worked, the issue is in:\n";
echo "- signup.php form submission\n";
echo "- auth.php processing\n";
echo "Check browser console (F12) when signing up\n";
echo "</pre>";

$conn->close();