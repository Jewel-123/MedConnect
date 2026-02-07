<?php
session_start();
require_once 'db.php';

// Update all prescriptions with NULL or empty status to 'finalized'
$result = $conn->query("
    UPDATE prescriptions_v2 
    SET status = 'finalized' 
    WHERE status IS NULL OR status = '' OR status = 'draft'
");

if ($result) {
    $affected = $conn->affected_rows;
    echo "✅ Success! Updated $affected prescriptions to 'finalized' status.<br><br>";
    echo "Now go back to <a href='patient_prescriptions.php'>My Prescriptions</a> and you should see the 'Order Medicine' button!<br><br>";
    echo "<strong>Remember to clear your browser cache (Ctrl + Shift + Delete) or hard refresh (Ctrl + F5)</strong>";
} else {
    echo "❌ Error: " . $conn->error;
}

// Show updated prescriptions
echo "<h2>Updated Prescriptions:</h2>";
$prescriptions = $conn->query("
    SELECT id, prescription_number, status 
    FROM prescriptions_v2 
    ORDER BY id DESC 
    LIMIT 10
");

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Prescription Number</th><th>Status</th></tr>";
while ($row = $prescriptions->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['prescription_number']}</td>";
    echo "<td><strong>{$row['status']}</strong></td>";
    echo "</tr>";
}
echo "</table>";