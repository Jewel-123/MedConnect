<?php
session_start();
require_once 'db.php';

echo "<h1>Doctor Dashboard Test</h1>";

// Check session
echo "<h2>Session Check:</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
echo "Admin Name: " . ($_SESSION['admin_name'] ?? 'NOT SET') . "\n";
echo "</pre>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>❌ No user logged in. Please login first.</p>";
    echo "<a href='login.php'>Go to Login</a>";
    exit;
}

// Check if user is a doctor
if ($_SESSION['role'] !== 'doctor') {
    echo "<p style='color: red;'>❌ User is not a doctor. Role: " . $_SESSION['role'] . "</p>";
    exit;
}

echo "<p style='color: green;'>✅ Doctor session is valid!</p>";

// Check doctor profile
$doctor_id = $_SESSION['user_id'];
$profile = $conn->query("
    SELECT u.*, d.* 
    FROM users u 
    LEFT JOIN doctor_profiles d ON u.id = d.user_id 
    WHERE u.id = $doctor_id
")->fetch_assoc();

echo "<h2>Doctor Profile:</h2>";
echo "<pre>";
print_r($profile);
echo "</pre>";

// Check if tables exist
echo "<h2>Database Tables Check:</h2>";
$tables = [
    'consultations',
    'consultation_sessions',
    'prescriptions_v2',
    'prescription_items_v2',
    'doctor_reviews',
    'doctor_availability',
    'doctor_earnings',
    'doctor_notifications'
];

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "✅ Table '$table' exists<br>";
    } else {
        echo "❌ Table '$table' MISSING<br>";
    }
}

// Check consultations columns
echo "<h2>Consultations Table Columns:</h2>";
$columns = $conn->query("SHOW COLUMNS FROM consultations");
echo "<ul>";
while ($col = $columns->fetch_assoc()) {
    echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
}
echo "</ul>";

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li><a href='doctor_dashboard.php'>Go to Doctor Dashboard</a></li>";
echo "<li><a href='index.php'>Go to Home</a></li>";
echo "</ol>";

$conn->close();
?>
