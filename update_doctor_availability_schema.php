<?php
require_once 'db.php';

echo "<h2>Verifying Doctor Profiles Schema</h2>";

$result = $conn->query("DESC doctor_profiles");
$cols = [];
while ($row = $result->fetch_assoc()) {
    $cols[] = $row['Field'];
    echo "<li>{$row['Field']} ({$row['Type']})</li>";
}

$missing = [];
if (!in_array('is_online', $cols)) $missing[] = "is_online TINYINT(1) DEFAULT 0";
if (!in_array('max_concurrent_chats', $cols)) $missing[] = "max_concurrent_chats INT DEFAULT 3";
if (!in_array('consultation_types', $cols)) $missing[] = "consultation_types VARCHAR(255) DEFAULT 'chat,audio,video'";

if (!empty($missing)) {
    echo "<p style='color:orange;'>Adding missing availability columns...</p>";
    foreach ($missing as $m) {
        $sql = "ALTER TABLE doctor_profiles ADD COLUMN $m";
        if ($conn->query($sql)) {
            echo "<p style='color:green;'>✓ Added $m</p>";
        } else {
            echo "<p style='color:red;'>✗ Error: " . $conn->error . "</p>";
        }
    }
} else {
    echo "<p style='color:green;'>✓ All availability columns exist.</p>";
}

echo "<p><a href='doctor_dashboard.php'>Back to Dashboard</a></p>";