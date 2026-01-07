<?php
/**
 * Force Update Admin Password
 * This will update the admin password to 'admin123' with a fresh hash
 */

require_once 'db.php';

echo "<h2>Updating Admin Password...</h2>";
echo "<hr>";

// Generate a fresh password hash for 'admin123'
$newPassword = 'admin123';
$passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

echo "<p><strong>New password hash generated:</strong><br>" . $passwordHash . "</p>";

// Update the admin account password
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = 'admin@medconnect.com'");
$stmt->bind_param("s", $passwordHash);

if ($stmt->execute()) {
    echo "<h3 style='color: green;'>✅ Admin Password Updated Successfully!</h3>";
    echo "<p><strong>Email:</strong> admin@medconnect.com</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    
    // Verify the password works
    $checkStmt = $conn->prepare("SELECT password FROM users WHERE email = 'admin@medconnect.com'");
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $user = $result->fetch_assoc();
    
    echo "<br><h3>Verification Test:</h3>";
    if (password_verify($newPassword, $user['password'])) {
        echo "<p style='color: green; font-weight: bold; font-size: 1.2em;'>✅ PASSWORD VERIFICATION SUCCESSFUL!</p>";
        echo "<p style='color: green;'>The password 'admin123' now works correctly!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Verification failed - something went wrong</p>";
    }
    
    $checkStmt->close();
    
    echo "<br><hr>";
    echo "<p><a href='login.php' style='background: #4CAF50; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 1.1em;'>Go to Login Page Now</a></p>";
    
} else {
    echo "<h3 style='color: red;'>❌ Error updating password</h3>";
    echo "<p>" . $stmt->error . "</p>";
}

$stmt->close();
$conn->close();
?>
