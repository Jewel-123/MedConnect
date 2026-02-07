<?php
require_once 'db.php';

echo "<h2>Consultations Table Debug</h2>";

// Check all consultations
$result = $conn->query("
    SELECT 
        c.*,
        u.full_name as patient_name,
        d.full_name as doctor_name
    FROM consultations c
    LEFT JOIN users u ON c.patient_id = u.id
    LEFT JOIN users d ON c.doctor_id = d.id
    ORDER BY c.created_at DESC
    LIMIT 20
");

echo "<h3>Recent Consultations (Last 20)</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr>
        <th>ID</th>
        <th>Patient</th>
        <th>Doctor</th>
        <th>Status</th>
        <th>Payment Status</th>
        <th>Symptoms</th>
        <th>Fee</th>
        <th>Created At</th>
      </tr>";

$count = 0;
while ($row = $result->fetch_assoc()) {
    $count++;
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['patient_name']}</td>";
    echo "<td>" . ($row['doctor_name'] ?? 'Not Assigned') . "</td>";
    echo "<td><strong>{$row['status']}</strong></td>";
    echo "<td><strong>{$row['payment_status']}</strong></td>";
    echo "<td>" . substr($row['symptoms'], 0, 50) . "...</td>";
    echo "<td>₹{$row['consultation_fee']}</td>";
    echo "<td>{$row['created_at']}</td>";
    echo "</tr>";
}

echo "</table>";
echo "<p>Total records shown: $count</p>";

// Check specifically for pending paid consultations
echo "<h3>Pending Paid Consultations (What doctors should see)</h3>";
$pendingResult = $conn->query("
    SELECT 
        c.*,
        u.full_name as patient_name,
        d.full_name as doctor_name
    FROM consultations c
    LEFT JOIN users u ON c.patient_id = u.id
    LEFT JOIN users d ON c.doctor_id = d.id
    WHERE c.status = 'pending' AND c.payment_status = 'paid'
    ORDER BY c.created_at DESC
");

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr>
        <th>ID</th>
        <th>Patient</th>
        <th>Doctor ID</th>
        <th>Doctor Name</th>
        <th>Status</th>
        <th>Payment Status</th>
        <th>Created At</th>
      </tr>";

$pendingCount = 0;
while ($row = $pendingResult->fetch_assoc()) {
    $pendingCount++;
    echo "<tr style='background: #d1fae5;'>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['patient_name']}</td>";
    echo "<td>" . ($row['doctor_id'] ?? 'NULL') . "</td>";
    echo "<td>" . ($row['doctor_name'] ?? 'Not Assigned') . "</td>";
    echo "<td>{$row['status']}</td>";
    echo "<td>{$row['payment_status']}</td>";
    echo "<td>{$row['created_at']}</td>";
    echo "</tr>";
}

echo "</table>";
echo "<p>Pending paid consultations found: $pendingCount</p>";

// Check schema
echo "<h3>Consultations Table Schema</h3>";
$schema = $conn->query("DESCRIBE consultations");
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
while ($col = $schema->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$col['Field']}</td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>{$col['Null']}</td>";
    echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";