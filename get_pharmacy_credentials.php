<?php
require_once 'db.php';

echo "<h2>Pharmacy Accounts</h2>";

// Check for existing pharmacy accounts
$result = $conn->query("
    SELECT u.id, u.email, u.full_name, u.status, pp.pharmacy_name
    FROM users u
    LEFT JOIN pharmacy_profiles pp ON u.id = pp.user_id
    WHERE u.role = 'pharmacy'
");

if ($result->num_rows > 0) {
    echo "<h3>Existing Pharmacy Accounts:</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Email</th><th>Full Name</th><th>Pharmacy Name</th><th>Status</th><th>Password</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td><strong>{$row['email']}</strong></td>";
        echo "<td>{$row['full_name']}</td>";
        echo "<td>" . ($row['pharmacy_name'] ?: 'Not Set') . "</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td><em>Password: pharmacy123</em> (or whatever was set during signup)</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No pharmacy accounts found. Creating test account...</p>";
    
    // Create a test pharmacy account
    $email = 'pharmacy@test.com';
    $password = password_hash('pharmacy123', PASSWORD_BCRYPT);
    $fullName = 'MediPlus Pharmacy';
    
    $stmt = $conn->prepare("
        INSERT INTO users (email, password, full_name, role, status, created_at)
        VALUES (?, ?, ?, 'pharmacy', 'approved', NOW())
    ");
    $stmt->bind_param("sss", $email, $password, $fullName);
    
    if ($stmt->execute()) {
        $userId = $stmt->insert_id;
        
        // Create pharmacy profile
        $pharmacyName = 'MediPlus Pharmacy';
        $licenseNumber = 'PH' . rand(10000, 99999);
        $address = '123 Medical Street, City';
        $phone = '9876543210';
        
        $stmt2 = $conn->prepare("
            INSERT INTO pharmacy_profiles (user_id, pharmacy_name, license_number, address, phone_number, operating_hours)
            VALUES (?, ?, ?, ?, ?, '9:00 AM - 9:00 PM')
        ");
        $stmt2->bind_param("issss", $userId, $pharmacyName, $licenseNumber, $address, $phone);
        $stmt2->execute();
        
        echo "<div style='background: #d1fae5; padding: 20px; border-radius: 8px; border: 2px solid #10b981;'>";
        echo "<h3 style='color: #065f46;'>✅ Test Pharmacy Account Created!</h3>";
        echo "<p><strong>Email:</strong> $email</p>";
        echo "<p><strong>Password:</strong> pharmacy123</p>";
        echo "<p><strong>Pharmacy Name:</strong> $pharmacyName</p>";
        echo "<p><strong>Status:</strong> Approved</p>";
        echo "<p><strong>License:</strong> $licenseNumber</p>";
        echo "</div>";
        
        echo "<hr>";
        echo "<h3>Login Instructions:</h3>";
        echo "<ol>";
        echo "<li>Go to: <a href='login.php'>http://localhost/MedConnect/login.php</a></li>";
        echo "<li>Enter email: <strong>$email</strong></li>";
        echo "<li>Enter password: <strong>pharmacy123</strong></li>";
        echo "<li>After login, go to: <a href='pharmacy_dashboard.php'>Pharmacy Dashboard</a></li>";
        echo "</ol>";
    } else {
        echo "<p style='color: red;'>Error creating pharmacy account: " . $conn->error . "</p>";
    }
}

echo "<hr>";
echo "<h3>Quick Access:</h3>";
echo "<ul>";
echo "<li><a href='login.php' style='color: #667eea; font-weight: bold;'>Login Page</a></li>";
echo "<li><a href='pharmacy_dashboard.php' style='color: #667eea; font-weight: bold;'>Pharmacy Dashboard</a></li>";
echo "</ul>";