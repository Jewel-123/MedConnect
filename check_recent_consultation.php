<?php
require_once 'db.php';

echo "<h2>🔍 Recent Consultation Check</h2>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
table { width: 100%; border-collapse: collapse; background: white; margin: 20px 0; }
th, td { padding: 10px; border: 1px solid #ddd; text-align: left; font-size: 12px; }
th { background: #0d9488; color: white; }
.success { background: #d1fae5; padding: 15px; border-left: 4px solid #10b981; margin: 10px 0; }
.error { background: #fee2e2; padding: 15px; border-left: 4px solid #ef4444; margin: 10px 0; }
.warning { background: #fef3c7; padding: 15px; border-left: 4px solid #f59e0b; margin: 10px 0; }
pre { background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 6px; }
</style>";

// Get patient and Emily
$patient_email = 'jewelbiju2028@mca.ajce.in';
$patient = $conn->query("SELECT id, full_name FROM users WHERE email = '$patient_email'")->fetch_assoc();
$patient_id = $patient['id'];

$emily = $conn->query("SELECT id, full_name FROM users WHERE role = 'doctor' AND full_name LIKE '%Emily%'")->fetch_assoc();
$emily_id = $emily['id'];

echo "<div class='success'>";
echo "<p>Patient: {$patient['full_name']} (ID: $patient_id)</p>";
echo "<p>Emily: {$emily['full_name']} (ID: $emily_id)</p>";
echo "</div>";

// Get most recent consultations (last 1 hour)
echo "<h3>Your Most Recent Consultations (Last Hour):</h3>";
$recent = $conn->query("
    SELECT c.*, d.full_name as doctor_name
    FROM consultations c
    LEFT JOIN users d ON c.doctor_id = d.id
    WHERE c.patient_id = $patient_id
      AND c.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ORDER BY c.created_at DESC
");

if ($recent->num_rows == 0) {
    echo "<div class='error'>❌ No consultations found in last hour</div>";
    
    // Check last 24 hours
    echo "<h3>Last 24 Hours:</h3>";
    $recent24 = $conn->query("
        SELECT c.*, d.full_name as doctor_name
        FROM consultations c
        LEFT JOIN users d ON c.doctor_id = d.id
        WHERE c.patient_id = $patient_id
          AND c.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY c.created_at DESC
        LIMIT 10
    ");
    
    if ($recent24->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Doctor</th><th>Status</th><th>Payment</th><th>Created</th></tr>";
        while ($c = $recent24->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$c['id']}</td>";
            echo "<td>" . ($c['doctor_name'] ?: 'NULL') . "</td>";
            echo "<td>{$c['status']}</td>";
            echo "<td>{$c['payment_status']}</td>";
            echo "<td>" . date('Y-m-d H:i:s', strtotime($c['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ No consultations in last 24 hours either</div>";
    }
    
} else {
    echo "<table>";
    echo "<tr><th>ID</th><th>Doctor ID</th><th>Doctor Name</th><th>Status</th><th>Payment Status</th><th>Symptoms</th><th>Created</th><th>Issues</th></tr>";
    
    while ($c = $recent->fetch_assoc()) {
        $issues = [];
        
        if ($c['doctor_id'] != $emily_id) {
            $issues[] = "❌ Doctor ID is {$c['doctor_id']}, not Emily ($emily_id)";
        }
        
        if (!in_array($c['status'], ['pending', 'assigned'])) {
            $issues[] = "❌ Status is '{$c['status']}', not 'pending' or 'assigned'";
        }
        
        if ($c['payment_status'] != 'paid') {
            $issues[] = "⚠️ Payment status is '{$c['payment_status']}', not 'paid'";
        }
        
        $color = empty($issues) ? '#d1fae5' : '#fee2e2';
        
        echo "<tr style='background: $color'>";
        echo "<td><strong>{$c['id']}</strong></td>";
        echo "<td>" . ($c['doctor_id'] ?: 'NULL') . "</td>";
        echo "<td>" . ($c['doctor_name'] ?: 'NONE') . "</td>";
        echo "<td><strong>{$c['status']}</strong></td>";
        echo "<td><strong>{$c['payment_status']}</strong></td>";
        echo "<td>" . substr($c['symptoms'], 0, 40) . "...</td>";
        echo "<td>" . date('H:i:s', strtotime($c['created_at'])) . "</td>";
        
        if (empty($issues)) {
            echo "<td style='color: green; font-weight: bold;'>✅ Should show in Emily's dashboard</td>";
        } else {
            echo "<td>" . implode('<br>', $issues) . "</td>";
        }
        
        echo "</tr>";
    }
    echo "</table>";
}

// Check what Emily's dashboard query returns
echo "<h3>Emily's Incoming Requests Query:</h3>";
echo "<pre>SELECT * FROM consultations
WHERE doctor_id = $emily_id
  AND status IN ('pending', 'assigned')
ORDER BY created_at DESC</pre>";

$emily_incoming = $conn->query("
    SELECT c.*, u.full_name as patient_name
    FROM consultations c
    JOIN users u ON c.patient_id = u.id
    WHERE c.doctor_id = $emily_id
      AND c.status IN ('pending', 'assigned')
    ORDER BY c.created_at DESC
");

echo "<div class='info'><strong>Results: {$emily_incoming->num_rows} consultation(s)</strong></div>";

if ($emily_incoming->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Patient</th><th>Status</th><th>Payment</th><th>Created</th></tr>";
    while ($r = $emily_incoming->fetch_assoc()) {
        echo "<tr style='background: #d1fae5'>";
        echo "<td>{$r['id']}</td>";
        echo "<td>{$r['patient_name']}</td>";
        echo "<td>{$r['status']}</td>";
        echo "<td>{$r['payment_status']}</td>";
        echo "<td>" . date('Y-m-d H:i:s', strtotime($r['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<div class='success'>✅ These {$emily_incoming->num_rows} should appear in Emily's dashboard</div>";
} else {
    echo "<div class='error'>❌ Nothing shows in Emily's incoming requests</div>";
}

// Check recent payments
echo "<h3>Your Recent Payments (Last Hour):</h3>";
$payments = $conn->query("
    SELECT * FROM payment_transactions
    WHERE user_id = $patient_id
      AND transaction_type = 'consultation_fee'
      AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ORDER BY created_at DESC
");

if ($payments->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Payment ID</th><th>Consultation ID</th><th>Amount</th><th>Status</th><th>Created</th></tr>";
    while ($p = $payments->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$p['id']}</td>";
        echo "<td>{$p['related_id']}</td>";
        echo "<td>₹{$p['amount']}</td>";
        echo "<td>{$p['status']}</td>";
        echo "<td>" . date('H:i:s', strtotime($p['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='warning'>No payments in last hour</div>";
}

echo "<h3>Diagnosis:</h3>";
echo "<div class='info'>";
echo "<p>If you just created a consultation, check the table above for issues:</p>";
echo "<ul>";
echo "<li>✅ <strong>doctor_id must be $emily_id</strong> (Emily's ID)</li>";
echo "<li>✅ <strong>status must be 'pending' or 'assigned'</strong></li>";
echo "<li>For full visibility: payment_status should be 'paid'</li>";
echo "</ul>";
echo "</div>";
