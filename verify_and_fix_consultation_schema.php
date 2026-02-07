<?php
require_once 'db.php';

echo "<h2>Verifying Consultations Table Schema</h2>";

$result = $conn->query("DESC consultations");
$has_payment_status = false;

echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
    if ($row['Field'] == 'payment_status') {
        $has_payment_status = true;
    }
}
echo "</table>";

if (!$has_payment_status) {
    echo "<p style='color:orange;'>Missing 'payment_status'. Adding it now...</p>";
    $sql = "ALTER TABLE consultations ADD COLUMN payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending' AFTER status";
    if ($conn->query($sql)) {
        echo "<p style='color:green;'>✓ Successfully added 'payment_status' column.</p>";
    } else {
        echo "<p style='color:red;'>✗ Error adding column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:green;'>✓ 'payment_status' column already exists.</p>";
}

echo "<p><a href='doctor_dashboard.php'>Back to Dashboard</a></p>";