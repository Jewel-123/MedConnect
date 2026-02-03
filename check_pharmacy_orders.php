<?php
session_start();
require_once 'db.php';

echo "<h1>Pharmacy Dashboard Diagnostic</h1>";

// 1. Check central pharmacy user
echo "<h2>1. Central Pharmacy User</h2>";
$pharmacy = $conn->query("
    SELECT id, email, full_name, role 
    FROM users 
    WHERE email = 'central.pharmacy@medconnect.com'
")->fetch_assoc();

if ($pharmacy) {
    echo "✅ Central Pharmacy exists:<br>";
    echo "ID: {$pharmacy['id']}<br>";
    echo "Email: {$pharmacy['email']}<br>";
    echo "Name: {$pharmacy['full_name']}<br>";
    echo "Role: {$pharmacy['role']}<br><br>";
    $pharmacyId = $pharmacy['id'];
} else {
    echo "❌ Central Pharmacy NOT FOUND!<br><br>";
    $pharmacyId = null;
}

// 2. Check prescription orders
echo "<h2>2. Recent Prescription Orders</h2>";
$orders = $conn->query("
    SELECT po.*, p.prescription_number, p.status as prescription_status
    FROM prescription_orders po
    LEFT JOIN prescriptions_v2 p ON po.prescription_id = p.id
    ORDER BY po.created_at DESC
    LIMIT 5
");

if ($orders->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Order ID</th><th>Order Number</th><th>Prescription ID</th><th>Pharmacy ID</th><th>Order Status</th><th>Prescription Status</th><th>Created</th></tr>";
    while ($order = $orders->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$order['id']}</td>";
        echo "<td>{$order['order_number']}</td>";
        echo "<td>{$order['prescription_id']}</td>";
        echo "<td>{$order['pharmacy_id']}</td>";
        echo "<td><strong>{$order['order_status']}</strong></td>";
        echo "<td><strong>{$order['prescription_status']}</strong></td>";
        echo "<td>{$order['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "❌ No prescription orders found!<br><br>";
}

// 3. Check prescriptions sent to pharmacy
echo "<h2>3. Prescriptions Sent to Pharmacy</h2>";
$prescriptions = $conn->query("
    SELECT id, prescription_number, status, pharmacy_id, sent_to_pharmacy_at
    FROM prescriptions_v2
    WHERE status = 'sent_to_pharmacy'
    ORDER BY id DESC
    LIMIT 5
");

if ($prescriptions->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Prescription Number</th><th>Status</th><th>Pharmacy ID</th><th>Sent At</th></tr>";
    while ($rx = $prescriptions->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$rx['id']}</td>";
        echo "<td>{$rx['prescription_number']}</td>";
        echo "<td><strong>{$rx['status']}</strong></td>";
        echo "<td>{$rx['pharmacy_id']}</td>";
        echo "<td>{$rx['sent_to_pharmacy_at']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "❌ No prescriptions with 'sent_to_pharmacy' status!<br><br>";
}

// 4. Check what pharmacy dashboard query would return
if ($pharmacyId) {
    echo "<h2>4. What Pharmacy Dashboard Should See (Pharmacy ID: $pharmacyId)</h2>";
    $dashboardQuery = $conn->query("
        SELECT p.*, 
               c.symptoms, c.diagnosis as consultation_diagnosis,
               pat.full_name as patient_name, pat.email as patient_email,
               doc.full_name as doctor_name,
               dp.specialization
        FROM prescriptions_v2 p
        JOIN consultations c ON p.consultation_id = c.id
        JOIN users pat ON p.patient_id = pat.id
        JOIN users doc ON p.doctor_id = doc.id
        LEFT JOIN doctor_profiles dp ON doc.id = dp.user_id
        WHERE p.pharmacy_id = $pharmacyId AND p.status = 'sent_to_pharmacy'
        ORDER BY p.sent_to_pharmacy_at DESC
    ");
    
    if ($dashboardQuery->num_rows > 0) {
        echo "✅ Found {$dashboardQuery->num_rows} pending prescription(s):<br>";
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Prescription #</th><th>Patient</th><th>Doctor</th><th>Status</th></tr>";
        while ($rx = $dashboardQuery->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$rx['id']}</td>";
            echo "<td>{$rx['prescription_number']}</td>";
            echo "<td>{$rx['patient_name']}</td>";
            echo "<td>{$rx['doctor_name']}</td>";
            echo "<td><strong>{$rx['status']}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ No prescriptions found for pharmacy dashboard!<br>";
        echo "<strong>Possible issues:</strong><br>";
        echo "- Prescriptions don't have consultation_id<br>";
        echo "- Prescriptions don't have correct pharmacy_id<br>";
        echo "- Status is not 'sent_to_pharmacy'<br>";
    }
}

echo "<br><br><a href='pharmacy_dashboard.php'>Back to Pharmacy Dashboard</a>";
?>
