<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    die("Please log in as a doctor first");
}

$doctor_id = $_SESSION['user_id'];

echo "<h2>Final Fix - Updating to 'assigned' Status</h2>";

// Update all consultations for this doctor to 'assigned'
$update = $conn->query("
    UPDATE consultations
    SET status = 'assigned', updated_at = NOW()
    WHERE doctor_id = $doctor_id
      AND (status = 'pending' OR status IS NULL OR status = '')
");

$affected = $conn->affected_rows;
echo "<p style='color: green; font-size: 18px;'><strong>✓ Updated {$affected} consultation(s) to 'assigned' status!</strong></p>";

// Verify
echo "<h3>Verification:</h3>";
$check = $conn->query("
    SELECT c.id, c.patient_id, c.status, u.full_name as patient_name
    FROM consultations c
    JOIN users u ON c.patient_id = u.id
    WHERE c.doctor_id = $doctor_id 
      AND c.status IN ('assigned', 'in_progress', 'paused')
");

if ($check->num_rows > 0) {
    echo "<div style='background: #d1fae5; border: 2px solid #10b981; padding: 20px; border-radius: 8px;'>";
    echo "<h2 style='color: #065f46; margin-top: 0;'>✅ SUCCESS!</h2>";
    echo "<p style='font-size: 18px;'>Found <strong>{$check->num_rows}</strong> active consultation(s)!</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Patient Name</th><th>Status</th></tr>";
    while ($row = $check->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['patient_name']}</td>";
        echo "<td style='color: green; font-weight: bold;'>{$row['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='margin-top: 20px; font-size: 16px;'><strong>Next steps:</strong></p>";
    echo "<ol style='font-size: 15px;'>";
    echo "<li>Go to your <a href='doctor_dashboard.php' style='color: #0d9488; font-weight: bold;'>Doctor Dashboard</a></li>";
    echo "<li>Click on <strong>Consultations</strong> in the menu</li>";
    echo "<li>You should see your active consultations with Start, Prescribe, and Complete buttons</li>";
    echo "<li><strong>From now on</strong>, when you accept new consultations, they will immediately appear in Active Consultations!</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<p style='color: red;'><strong>No active consultations found.</strong></p>";
    
    // Show what we have
    $all = $conn->query("
        SELECT id, patient_id, status FROM consultations WHERE doctor_id = $doctor_id
    ");
    echo "<p>Current consultations:</p>";
    echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Patient</th><th>Status</th></tr>";
    while ($row = $all->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['patient_id']}</td><td>{$row['status']}</td></tr>";
    }
    echo "</table>";
}