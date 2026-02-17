<?php
require_once 'db.php';

echo "<h2>🔍 Why Aren't Consultations Showing in Emily's Dashboard?</h2>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
table { width: 100%; border-collapse: collapse; background: white; margin: 20px 0; }
th, td { padding: 10px; border: 1px solid #ddd; text-align: left; font-size: 12px; }
th { background: #0d9488; color: white; }
.success { background: #d1fae5; padding: 15px; border-left: 4px solid #10b981; margin: 10px 0; }
.error { background: #fee2e2; padding: 15px; border-left: 4px solid #ef4444; margin: 10px 0; }
.info { background: #e0f2fe; padding: 15px; border-left: 4px solid #0284c7; margin: 10px 0; }
pre { background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 6px; overflow-x: auto; }
</style>";

// Get Emily
$emily = $conn->query("SELECT id FROM users WHERE role = 'doctor' AND full_name LIKE '%Emily%'")->fetch_assoc();
$emily_id = $emily['id'];

echo "<div class='success'>Emily's ID: $emily_id</div>";

// THE EXACT QUERY FROM doctor_api.php
echo "<h3>1. Running EXACT Query from doctor_api.php</h3>";
echo "<pre>SELECT c.*, u.full_name as patient_name
FROM consultations c
JOIN users u ON c.patient_id = u.id
WHERE c.doctor_id = $emily_id
  AND c.status IN ('pending', 'assigned')
ORDER BY c.created_at DESC</pre>";

$incoming = $conn->query("
    SELECT c.*, u.full_name as patient_name
    FROM consultations c
    JOIN users u ON c.patient_id = u.id
    WHERE c.doctor_id = $emily_id
      AND c.status IN ('pending', 'assigned')
    ORDER BY c.created_at DESC
");

echo "<div class='info'><strong>Results: {$incoming->num_rows} consultation(s) found</strong></div>";

if ($incoming->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Patient</th><th>Status</th><th>Payment Status</th><th>Symptoms</th><th>Created</th></tr>";
    while ($r = $incoming->fetch_assoc()) {
        echo "<tr style='background: #d1fae5'>";
        echo "<td>{$r['id']}</td>";
        echo "<td>{$r['patient_name']}</td>";
        echo "<td><strong>{$r['status']}</strong></td>";
        echo "<td><strong>{$r['payment_status']}</strong></td>";
        echo "<td>" . substr($r['symptoms'], 0, 40) . "...</td>";
        echo "<td>" . date('Y-m-d H:i:s', strtotime($r['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<div class='success'>✅ These {$incoming->num_rows} consultations SHOULD appear in Emily's Incoming Requests</div>";
} else {
    echo "<div class='error'>❌ No results - that's why Emily's dashboard is empty!</div>";
}

// Check what's actually in consultations 42-53
echo "<h3>2. What's Actually in Your Paid Consultations (42-53)?</h3>";
$actual = $conn->query("
    SELECT id, doctor_id, status, payment_status, symptoms
    FROM consultations
    WHERE id IN (42,43,44,45,46,47,48,49,50,51,52,53)
      AND doctor_id = $emily_id
    ORDER BY id DESC
");

echo "<table>";
echo "<tr><th>ID</th><th>Doctor ID</th><th>Status</th><th>Payment Status</th><th>Matches Query?</th></tr>";

while ($c = $actual->fetch_assoc()) {
    $matches = in_array($c['status'], ['pending', 'assigned']);
    $color = $matches ? '#d1fae5' : '#fee2e2';
    
    echo "<tr style='background: $color'>";
    echo "<td>{$c['id']}</td>";
    echo "<td>{$c['doctor_id']}</td>";
    echo "<td><strong>{$c['status']}</strong></td>";
    echo "<td>{$c['payment_status']}</td>";
    
    if ($matches) {
        echo "<td style='color: green; font-weight: bold;'>✅ YES</td>";
    } else {
        echo "<td style='color: red; font-weight: bold;'>❌ NO - Status is '{$c['status']}', not 'pending' or 'assigned'</td>";
    }
    
    echo "</tr>";
}
echo "</table>";

// Show what statuses actually exist
echo "<h3>3. What Status Values Do Your Consultations Have?</h3>";
$statuses = $conn->query("
    SELECT status, COUNT(*) as count
    FROM consultations
    WHERE id IN (42,43,44,45,46,47,48,49,50,51,52,53)
    GROUP BY status
");

echo "<table>";
echo "<tr><th>Status</th><th>Count</th><th>Will Show in Incoming?</th></tr>";
while ($s = $statuses->fetch_assoc()) {
    $will_show = in_array($s['status'], ['pending', 'assigned']) ? '✅ YES' : '❌ NO';
    $color = in_array($s['status'], ['pending', 'assigned']) ? '#d1fae5' : '#fee2e2';
    
    echo "<tr style='background: $color'>";
    echo "<td><strong>{$s['status']}</strong></td>";
    echo "<td>{$s['count']}</td>";
    echo "<td>$will_show</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>4. The Problem</h3>";
$non_pending = $conn->query("SELECT COUNT(*) as cnt FROM consultations WHERE id IN (42,43,44,45,46,47,48,49,50,51,52,53) AND status NOT IN ('pending', 'assigned')")->fetch_assoc()['cnt'];

if ($non_pending > 0) {
    echo "<div class='error'>";
    echo "<p><strong>FOUND THE PROBLEM!</strong></p>";
    echo "<p>$non_pending consultation(s) have status other than 'pending' or 'assigned'</p>";
    echo "<p>The dashboard query ONLY shows consultations with status IN ('pending', 'assigned')</p>";
    echo "<p><strong>Fix: Update their status to 'pending'</strong></p>";
    
    if (!isset($_POST['fix_status'])) {
        echo "<form method='POST'>";
        echo "<input type='hidden' name='fix_status' value='1'>";
        echo "<button type='submit' style='background:#ef4444;color:white;padding:15px 30px;border:none;border-radius:6px;cursor:pointer;font-size:16px;font-weight:bold;'>🔧 Set All to Status = 'pending'</button>";
        echo "</form>";
    }
    echo "</div>";
}

// Fix status
if (isset($_POST['fix_status'])) {
    $result = $conn->query("
        UPDATE consultations
        SET status = 'pending',
            updated_at = NOW()
        WHERE id IN (42,43,44,45,46,47,48,49,50,51,52,53)
          AND doctor_id = $emily_id
    ");
    
    if ($result) {
        echo "<div class='success'>";
        echo "<h2>✅ FIXED!</h2>";
        echo "<p>Updated {$conn->affected_rows} consultation(s) to status = 'pending'</p>";
        echo "<p><a href='doctor_dashboard.php' style='background:#0d9488;color:white;padding:12px 24px;text-decoration:none;border-radius:6px;display:inline-block;margin-top:10px;'>Go to Emily's Dashboard</a></p>";
        echo "</div>";
        
        // Re-run query
        echo "<h3>Verification - What Shows Now:</h3>";
        $verify = $conn->query("
            SELECT c.*, u.full_name as patient_name
            FROM consultations c
            JOIN users u ON c.patient_id = u.id
            WHERE c.doctor_id = $emily_id
              AND c.status IN ('pending', 'assigned')
        ");
        
        echo "<div class='success'><strong>{$verify->num_rows} consultation(s) will now appear in Incoming Requests!</strong></div>";
    }
}
