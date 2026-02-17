<?php
require_once 'db.php';

echo "<h2>🔍 Debug: Emily's Incoming Requests</h2>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
table { width: 100%; border-collapse: collapse; background: white; margin: 20px 0; }
th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
th { background: #0d9488; color: white; }
.error { background: #fee2e2; padding: 15px; border-left: 4px solid #ef4444; margin: 10px 0; }
.success { background: #d1fae5; padding: 15px; border-left: 4px solid #10b981; margin: 10px 0; }
.info { background: #e0f2fe; padding: 15px; border-left: 4px solid #0284c7; margin: 10px 0; }
</style>";

// Find Emily's doctor_id
$emily = $conn->query("SELECT id, full_name, email FROM users WHERE role = 'doctor' AND (full_name LIKE '%Emily%' OR email LIKE '%emily%')");

if ($emily->num_rows > 0) {
    $doctor = $emily->fetch_assoc();
    $doctor_id = $doctor['id'];
    $doctor_name = $doctor['full_name'];
    
    echo "<div class='success'>Found Doctor: <strong>$doctor_name</strong> (ID: $doctor_id, Email: {$doctor['email']})</div>";
    
    // Check ALL consultations for this doctor
    echo "<h3>All Consultations for $doctor_name:</h3>";
    $all_cons = $conn->query("
        SELECT c.*, u.full_name as patient_name, u.email as patient_email
        FROM consultations c
        JOIN users u ON c.patient_id = u.id
        WHERE c.doctor_id = $doctor_id
        ORDER BY c.created_at DESC
        LIMIT 20
    ");
    
    if ($all_cons->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Patient</th><th>Symptoms</th><th>Status</th><th>Payment</th><th>Created</th></tr>";
        while ($c = $all_cons->fetch_assoc()) {
            $status_color = $c['status'] == 'pending' ? '#fef3c7' : '#e5e7eb';
            $payment_color = $c['payment_status'] == 'paid' ? '#d1fae5' : '#fee2e2';
            echo "<tr style='background: $status_color'>";
            echo "<td>{$c['id']}</td>";
            echo "<td>{$c['patient_name']}<br><small>{$c['patient_email']}</small></td>";
            echo "<td>" . substr($c['symptoms'], 0, 50) . "...</td>";
            echo "<td style='background: $status_color'><strong>{$c['status']}</strong></td>";
            echo "<td style='background: $payment_color'><strong>{$c['payment_status']}</strong></td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($c['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ No consultations found for $doctor_name</div>";
    }
    
    // Check what SHOULD appear in incoming requests
    echo "<h3>What SHOULD appear in Incoming Requests:</h3>";
    echo "<div class='info'>Query: status IN ('pending', 'assigned') AND payment_status = 'paid'</div>";
    
    $should_show = $conn->query("
        SELECT c.*, u.full_name as patient_name
        FROM consultations c
        JOIN users u ON c.patient_id = u.id
        WHERE c.doctor_id = $doctor_id
          AND c.status IN ('pending', 'assigned')
        ORDER BY c.created_at DESC
    ");
    
    if ($should_show->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Patient</th><th>Status</th><th>Payment</th><th>Should Show?</th></tr>";
        while ($c = $should_show->fetch_assoc()) {
            $should = ($c['payment_status'] == 'paid') ? '✅ YES' : '❌ NO (not paid)';
            $color = ($c['payment_status'] == 'paid') ? '#d1fae5' : '#fee2e2';
            echo "<tr style='background: $color'>";
            echo "<td>{$c['id']}</td>";
            echo "<td>{$c['patient_name']}</td>";
            echo "<td>{$c['status']}</td>";
            echo "<td>{$c['payment_status']}</td>";
            echo "<td><strong>$should</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ No consultations with status 'pending' or 'assigned'</div>";
        echo "<div class='info'>💡 This means consultations might have a different status. Check the 'All Consultations' table above.</div>";
    }
    
    // Check recent payments
    echo "<h3>Recent Payment Transactions:</h3>";
    $payments = $conn->query("
        SELECT pt.*, c.doctor_id, c.status as cons_status, u.full_name as patient_name
        FROM payment_transactions pt
        LEFT JOIN consultations c ON pt.related_id = c.id AND pt.transaction_type = 'consultation_fee'
        LEFT JOIN users u ON pt.user_id = u.id
        WHERE pt.transaction_type = 'consultation_fee'
          AND pt.created_at >= DATE_SUB(NOW(), INTERVAL 2 DAY)
        ORDER BY pt.created_at DESC
        LIMIT 10
    ");
    
    if ($payments->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>Payment ID</th><th>Patient</th><th>Consultation ID</th><th>Doctor ID</th><th>Amount</th><th>Status</th><th>Created</th></tr>";
        while ($p = $payments->fetch_assoc()) {
            $emily_highlight = ($p['doctor_id'] == $doctor_id) ? 'background: #fef3c7; font-weight: bold;' : '';
            echo "<tr style='$emily_highlight'>";
            echo "<td>{$p['id']}</td>";
            echo "<td>{$p['patient_name']}</td>";
            echo "<td>{$p['related_id']}</td>";
            echo "<td>" . ($p['doctor_id'] ?: '<span style="color:red">NULL</span>') . "</td>";
            echo "<td>₹{$p['amount']}</td>";
            echo "<td>{$p['status']}</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($p['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} else {
    echo "<div class='error'>❌ Doctor Emily not found in database</div>";
}

// Also check if there are any consultations with NULL doctor_id and paid status
echo "<h3>Orphaned Paid Consultations (doctor_id = NULL):</h3>";
$orphaned = $conn->query("
    SELECT c.*, u.full_name as patient_name
    FROM consultations c
    JOIN users u ON c.patient_id = u.id
    WHERE c.doctor_id IS NULL
      AND c.payment_status = 'paid'
      AND c.created_at >= DATE_SUB(NOW(), INTERVAL 2 DAY)
    ORDER BY c.created_at DESC
");

if ($orphaned->num_rows > 0) {
    echo "<div class='error'>⚠️ Found {$orphaned->num_rows} paid consultations without a doctor assigned!</div>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Patient</th><th>Symptoms</th><th>Payment</th><th>Status</th><th>Created</th></tr>";
    while ($c = $orphaned->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$c['id']}</td>";
        echo "<td>{$c['patient_name']}</td>";
        echo "<td>" . substr($c['symptoms'], 0, 50) . "...</td>";
        echo "<td>{$c['payment_status']}</td>";
        echo "<td>{$c['status']}</td>";
        echo "<td>" . date('Y-m-d H:i', strtotime($c['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='success'>✅ No orphaned paid consultations found</div>";
}
