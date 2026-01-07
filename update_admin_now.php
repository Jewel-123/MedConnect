<?php
/**
 * Direct Password Update - Simple Version
 */

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "medconnect";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Direct Admin Password Update</h2><hr>";

// Generate new password hash
$newPasswordHash = password_hash('admin123', PASSWORD_DEFAULT);

echo "<p><strong>Step 1:</strong> Generated new password hash</p>";
echo "<p style='font-size: 0.8em; color: #666;'>" . $newPasswordHash . "</p>";

// Update the password
$sql = "UPDATE users SET password = '$newPasswordHash' WHERE email = 'admin@medconnect.com'";

if ($conn->query($sql) === TRUE) {
    echo "<h3 style='color: green;'>✅ SUCCESS! Password Updated</h3>";
    
    // Verify it worked
    $result = $conn->query("SELECT password FROM users WHERE email = 'admin@medconnect.com'");
    $row = $result->fetch_assoc();
    
    echo "<p><strong>Step 2:</strong> Verification</p>";
    if (password_verify('admin123', $row['password'])) {
        echo "<h2 style='color: green; background: #d4edda; padding: 20px; border-radius: 10px;'>✅✅✅ PASSWORD IS NOW CORRECT! ✅✅✅</h2>";
        echo "<p style='font-size: 1.2em;'><strong>You can now login with:</strong></p>";
        echo "<ul style='font-size: 1.1em;'>";
        echo "<li>Email: <strong>admin@medconnect.com</strong></li>";
        echo "<li>Password: <strong>admin123</strong></li>";
        echo "</ul>";
        echo "<br>";
        echo "<a href='login.php' style='background: #28a745; color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-size: 1.2em; display: inline-block;'>GO TO LOGIN PAGE</a>";
    } else {
        echo "<p style='color: red;'>❌ Verification failed</p>";
    }
} else {
    echo "<p style='color: red;'>Error: " . $conn->error . "</p>";
}

$conn->close();
?>
