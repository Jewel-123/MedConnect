<?php
session_start();

echo "=== Session Check ===\n";
echo "Logged in: " . (isset($_SESSION['user_id']) ? "YES" : "NO") . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n\n";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacy') {
    echo "❌ NOT LOGGED IN AS PHARMACY!\n";
    echo "You need to login as pharmacy@medconnect.com first.\n\n";
    
    // Check if pharmacy user exists
    require_once 'db.php';
    $res = $conn->query("SELECT id, email, role FROM users WHERE email = 'pharmacy@medconnect.com'");
    if ($row = $res->fetch_assoc()) {
        echo "Pharmacy user found in database:\n";
        echo "  ID: {$row['id']}\n";
        echo "  Email: {$row['email']}\n";
        echo "  Role: {$row['role']}\n";
    }
} else {
    echo "✅ LOGGED IN AS PHARMACY - Session is valid!\n";
}
?>
