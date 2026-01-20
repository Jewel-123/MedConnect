<?php
$conn = new mysqli("localhost", "root", "", "medconnect");

if ($conn->connect_error) {
    echo "❌ Cannot connect to medconnect database\n";
    echo "Error: " . $conn->connect_error . "\n";
} else {
    echo "✅ Connected to medconnect database!\n\n";
    
    $result = $conn->query("SELECT * FROM users WHERE email = 'admin@medconnect.com'");
    
    if ($result && $result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        echo "✅ ADMIN FOUND!\n\n";
        echo "ID: {$admin['id']}\n";
        echo "Name: {$admin['name']}\n";
        echo "Email: {$admin['email']}\n";
        echo "Role: {$admin['role']}\n";
        echo "Status: {$admin['status']}\n\n";
        echo "🎉 YOU CAN NOW LOGIN! 🎉\n\n";
        echo "Go to: http://localhost/medconnect/login.php\n";
        echo "Email: admin@medconnect.com\n";
        echo "Password: admin123\n";
    } else {
        echo "❌ Admin not found in database\n";
    }
}
?>
