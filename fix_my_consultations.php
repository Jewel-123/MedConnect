<?php
require_once 'db.php';

echo "<h2>🔧 Fix Your Paid Consultations</h2>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
table { width: 100%; border-collapse: collapse; background: white; margin: 20px 0; }
th, td { padding: 12px; border: 1px solid #ddd; text-align: left; font-size: 13px; }
th { background: #0d9488; color: white; }
.success { background: #d1fae5; padding: 15px; border-left: 4px solid #10b981; margin: 10px 0; }
.error { background: #fee2e2; padding: 15px; border-left: 4px solid #ef4444; margin: 10px 0; }
.warning { background: #fef3c7; padding: 15px; border-left: 4px solid #f59e0b; margin: 10px 0; }
</style>";

// Get Emily's ID
$emily = $conn->query("SELECT id FROM users WHERE role = 'doctor' AND full_name LIKE '%Emily%'")->fetch_assoc();
$emily_id = $emily['id'];

echo "<div class='success'>Emily's Doctor ID: $emily_id</div>";

// Check consultations 42-53
$consultation_ids = '42,43,44,45,46,47,48,49,50,51,52,53';

echo "<h3>Checking Paid Consultations (IDs: $consultation_ids)</h3>";

$consultations = $conn->query("
    SELECT id, doctor_id, status, payment_status, symptoms
    FROM consultations
    WHERE id IN ($consultation_ids)
    ORDER BY id DESC
");

echo "<table>";
echo "<tr><th>ID</th><th>Doctor ID</th><th>Status</th><th>Payment Status</th><th>Issue</th><th>Action</th></tr>";

$need_fix = [];

while ($c = $consultations->fetch_assoc()) {
    $issues = [];
    $needs_fixing = false;
    
    if ($c['doctor_id'] == null) {
        $issues[] = "❌ No doctor assigned";
        $needs_fixing = true;
    } elseif ($c['doctor_id'] != $emily_id) {
        $issues[] = "⚠️ Assigned to wrong doctor (ID: {$c['doctor_id']})";
        $needs_fixing = true;
    }
    
    if ($c['status'] != 'pending') {
        $issues[] = "Status: {$c['status']} (should be 'pending')";
        $needs_fixing = true;
    }
    
    if ($c['payment_status'] != 'paid') {
        $issues[] = "Payment: {$c['payment_status']} (should be 'paid')";
        $needs_fixing = true;
    }
    
    $color = $needs_fixing ? '#fee2e2' : '#d1fae5';
    
    echo "<tr style='background: $color'>";
    echo "<td>{$c['id']}</td>";
    echo "<td>" . ($c['doctor_id'] ?: '<strong style="color:red">NULL</strong>') . "</td>";
    echo "<td>{$c['status']}</td>";
    echo "<td>{$c['payment_status']}</td>";
    echo "<td>" . implode('<br>', $issues) . "</td>";
    
    if ($needs_fixing) {
        echo "<td>Will Fix ✓</td>";
        $need_fix[] = $c['id'];
    } else {
        echo "<td>✅ OK</td>";
    }
    
    echo "</tr>";
}

echo "</table>";

if (count($need_fix) > 0) {
    echo "<div class='error'><strong>" . count($need_fix) . " consultation(s) need fixing</strong></div>";
    
    if (!isset($_POST['execute_fix'])) {
        echo "<form method='POST'>";
        echo "<input type='hidden' name='execute_fix' value='1'>";
        echo "<input type='hidden' name='consultation_ids' value='" . implode(',', $need_fix) . "'>";
        echo "<input type='hidden' name='emily_id' value='$emily_id'>";
        echo "<button type='submit' style='background:#ef4444;color:white;padding:15px 30px;border:none;border-radius:6px;cursor:pointer;font-size:16px;font-weight:bold;'>🔧 FIX NOW - Assign to Emily & Set to Pending</button>";
        echo "</form>";
        
        echo "<div class='warning' style='margin-top:20px'>";
        echo "<strong>This will:</strong>";
        echo "<ul>";
        echo "<li>Set doctor_id = $emily_id (Emily)</li>";
        echo "<li>Set status = 'pending'</li>";
        echo "<li>Set payment_status = 'paid'</li>";
        echo "<li>Update " . count($need_fix) . " consultation(s)</li>";
        echo "</ul>";
        echo "</div>";
    }
} else {
    echo "<div class='success'>✅ All consultations are correctly configured!</div>";
}

// Execute Fix
if (isset($_POST['execute_fix'])) {
    $fix_ids = $_POST['consultation_ids'];
    $emily_id = $_POST['emily_id'];
    
    echo "<h3>Executing Fix...</h3>";
    
    $result = $conn->query("
        UPDATE consultations
        SET doctor_id = $emily_id,
            status = 'pending',
            payment_status = 'paid',
            updated_at = NOW()
        WHERE id IN ($fix_ids)
    ");
    
    if ($result) {
        $affected = $conn->affected_rows;
        echo "<div class='success'>";
        echo "<h2>✅ SUCCESS!</h2>";
        echo "<p>Fixed $affected consultation(s)</p>";
        echo "<p><strong>These consultations should now appear in Emily's Incoming Requests!</strong></p>";
        echo "<p style='margin-top:20px'>";
        echo "<a href='doctor_dashboard.php' style='background:#0d9488;color:white;padding:12px 24px;text-decoration:none;border-radius:6px;display:inline-block;'>📊 Go to Emily's Dashboard</a>";
        echo "</p>";
        echo "</div>";
        
        // Verify
        echo "<h3>Verification:</h3>";
        $verify = $conn->query("
            SELECT id, doctor_id, status, payment_status
            FROM consultations
            WHERE id IN ($fix_ids)
        ");
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Doctor ID</th><th>Status</th><th>Payment Status</th></tr>";
        while ($v = $verify->fetch_assoc()) {
            echo "<tr style='background: #d1fae5'>";
            echo "<td>{$v['id']}</td>";
            echo "<td>{$v['doctor_id']}</td>";
            echo "<td>{$v['status']}</td>";
            echo "<td>{$v['payment_status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<div class='error'>❌ Fix failed: " . $conn->error . "</div>";
    }
}
