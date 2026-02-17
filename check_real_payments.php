<?php
require_once 'db.php';

echo "<h2>🔍 Real Payment Check</h2>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
table { width: 100%; border-collapse: collapse; background: white; margin: 20px 0; }
th, td { padding: 12px; border: 1px solid #ddd; text-align: left; font-size: 13px; }
th { background: #0d9488; color: white; }
.success { background: #d1fae5; padding: 15px; border-left: 4px solid #10b981; margin: 10px 0; }
.error { background: #fee2e2; padding: 15px; border-left: 4px solid #ef4444; margin: 10px 0; }
.warning { background: #fef3c7; padding: 15px; border-left: 4px solid #f59e0b; margin: 10px 0; }
</style>";

// Check your recent payments
$patient_email = 'jewelbiju2028@mca.ajce.in'; // Replace with actual patient email

echo "<h3>1. Finding Patient Account</h3>";
$patient = $conn->query("SELECT id, full_name, email FROM users WHERE email = '$patient_email'")->fetch_assoc();

if (!$patient) {
    echo "<div class='error'>❌ Patient not found with email: $patient_email</div>";
    echo "<p>Please provide the correct patient email you used for payments.</p>";
    exit;
}

$patient_id = $patient['id'];
echo "<div class='success'>✅ Found Patient: {$patient['full_name']} (ID: $patient_id)</div>";

// Check payment transactions
echo "<h3>2. Your Recent Payments (Last 24 hours)</h3>";
$payments = $conn->query("
    SELECT * FROM payment_transactions
    WHERE user_id = $patient_id
      AND transaction_type = 'consultation_fee'
      AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY created_at DESC
");

if ($payments->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Payment ID</th><th>Consultation ID</th><th>Amount</th><th>Status</th><th>Payment Method</th><th>Created</th></tr>";
    $paid_consultation_ids = [];
    while ($p = $payments->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$p['id']}</td>";
        echo "<td>{$p['related_id']}</td>";
        echo "<td>₹{$p['amount']}</td>";
        echo "<td style='background:" . ($p['status'] == 'completed' ? '#d1fae5' : '#fee2e2') . "'>{$p['status']}</td>";
        echo "<td>{$p['payment_method']}</td>";
        echo "<td>" . date('Y-m-d H:i:s', strtotime($p['created_at'])) . "</td>";
        echo "</tr>";
        if ($p['status'] == 'completed') {
            $paid_consultation_ids[] = $p['related_id'];
        }
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ No payments found in last 24 hours</div>";
}

// Check consultations linked to those payments
if (!empty($paid_consultation_ids)) {
    echo "<h3>3. Consultations from Your Payments</h3>";
    $ids_str = implode(',', $paid_consultation_ids);
    
    $consultations = $conn->query("
        SELECT c.*, u.full_name as doctor_name
        FROM consultations c
        LEFT JOIN users u ON c.doctor_id = u.id
        WHERE c.id IN ($ids_str)
    ");
    
    if ($consultations->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Doctor ID</th><th>Doctor Name</th><th>Status</th><th>Payment Status</th><th>Symptoms</th><th>Created</th></tr>";
        while ($c = $consultations->fetch_assoc()) {
            $doctor_color = $c['doctor_id'] ? '#d1fae5' : '#fee2e2';
            echo "<tr>";
            echo "<td>{$c['id']}</td>";
            echo "<td style='background: $doctor_color'>" . ($c['doctor_id'] ?: '<strong style="color:red">NULL</strong>') . "</td>";
            echo "<td>" . ($c['doctor_name'] ?: '<span style="color:red">NO DOCTOR</span>') . "</td>";
            echo "<td>{$c['status']}</td>";
            echo "<td>{$c['payment_status']}</td>";
            echo "<td>" . substr($c['symptoms'], 0, 40) . "...</td>";
            echo "<td>" . date('Y-m-d H:i:s', strtotime($c['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Count issues
        $null_doctor = $conn->query("SELECT COUNT(*) as cnt FROM consultations WHERE id IN ($ids_str) AND doctor_id IS NULL")->fetch_assoc()['cnt'];
        
        if ($null_doctor > 0) {
            echo "<div class='error'>⚠️ <strong>PROBLEM FOUND:</strong> $null_doctor consultation(s) have NULL doctor_id</div>";
            echo "<div class='warning'>These consultations won't appear in any doctor's dashboard because they're not assigned to anyone.</div>";
        } else {
            echo "<div class='success'>✅ All consultations have a doctor assigned</div>";
        }
    }
}

// Find Emily
echo "<h3>4. Emily's Account</h3>";
$emily = $conn->query("SELECT id, full_name, email FROM users WHERE role = 'doctor' AND full_name LIKE '%Emily%'")->fetch_assoc();
if ($emily) {
    echo "<div class='success'>Found: {$emily['full_name']} (ID: {$emily['id']}, Email: {$emily['email']})</div>";
    
    // What Emily should see
    echo "<h3>5. What Should Appear in Emily's Incoming Requests</h3>";
    echo "<div class='warning'>Query: doctor_id = {$emily['id']} AND status IN ('pending', 'assigned') AND payment_status = 'paid'</div>";
    
    $emily_incoming = $conn->query("
        SELECT c.*, u.full_name as patient_name
        FROM consultations c
        JOIN users u ON c.patient_id = u.id
        WHERE c.doctor_id = {$emily['id']}
          AND c.status IN ('pending', 'assigned')
          AND c.payment_status = 'paid'
    ");
    
    if ($emily_incoming->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Patient</th><th>Status</th><th>Payment</th><th>Created</th></tr>";
        while ($r = $emily_incoming->fetch_assoc()) {
            echo "<tr style='background: #d1fae5'>";
            echo "<td>{$r['id']}</td><td>{$r['patient_name']}</td><td>{$r['status']}</td><td>{$r['payment_status']}</td>";
            echo "<td>" . date('Y-m-d H:i:s', strtotime($r['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<div class='success'>✅ {$emily_incoming->num_rows} consultation(s) should appear for Emily</div>";
    } else {
        echo "<div class='error'>❌ Nothing will appear in Emily's incoming requests</div>";
    }
}

echo "<h3>6. Fix Required?</h3>";
if ($null_doctor > 0) {
    echo "<div class='error'>";
    echo "<p><strong>YES - You have $null_doctor paid consultation(s) without a doctor assigned!</strong></p>";
    echo "<p>To fix: Assign them to Emily (ID: {$emily['id']})</p>";
    echo "<form method='POST' style='margin-top:20px'>";
    echo "<input type='hidden' name='fix_now' value='1'>";
    echo "<input type='hidden' name='emily_id' value='{$emily['id']}'>";
    echo "<input type='hidden' name='consultation_ids' value='$ids_str'>";
    echo "<button type='submit' style='background:#0d9488;color:white;padding:12px 24px;border:none;border-radius:6px;cursor:pointer;font-size:16px;'>🔧 Fix Now - Assign to Emily</button>";
    echo "</form>";
    echo "</div>";
}

// Handle fix
if (isset($_POST['fix_now'])) {
    $emily_id = $_POST['emily_id'];
    $cons_ids = $_POST['consultation_ids'];
    
    $result = $conn->query("
        UPDATE consultations
        SET doctor_id = $emily_id,
            status = 'pending',
            updated_at = NOW()
        WHERE id IN ($cons_ids)
          AND doctor_id IS NULL
    ");
    
    if ($result) {
        echo "<div class='success'>✅ Fixed! Consultations assigned to Emily. <a href='doctor_dashboard.php'>Go to Dashboard</a></div>";
    } else {
        echo "<div class='error'>❌ Fix failed: " . $conn->error . "</div>";
    }
}
