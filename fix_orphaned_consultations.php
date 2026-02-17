<?php
require_once 'db.php';

echo "<h2>🔧 Fixing Orphaned Consultations</h2>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.success { background: #d1fae5; padding: 15px; border-left: 4px solid #10b981; margin: 10px 0; }
.error { background: #fee2e2; padding: 15px; border-left: 4px solid #ef4444; margin: 10px 0; }
.info { background: #e0f2fe; padding: 15px; border-left: 4px solid #0284c7; margin: 10px 0; }
table { width: 100%; border-collapse: collapse; background: white; margin: 20px 0; }
th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
th { background: #0d9488; color: white; }
</style>";

// Get Emily's doctor_id
$emily = $conn->query("SELECT id, full_name FROM users WHERE role = 'doctor' AND full_name LIKE '%Emily%'")->fetch_assoc();

if (!$emily) {
    echo "<div class='error'>❌ Emily not found in database</div>";
    exit;
}

$emily_id = $emily['id'];
$emily_name = $emily['full_name'];

echo "<div class='info'>Found Doctor: <strong>$emily_name</strong> (ID: $emily_id)</div>";

// Find orphaned paid consultations
$orphaned = $conn->query("
    SELECT c.*, u.full_name as patient_name
    FROM consultations c
    JOIN users u ON c.patient_id = u.id
    WHERE c.doctor_id IS NULL
      AND c.payment_status = 'paid'
      AND c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY c.created_at DESC
");

if ($orphaned->num_rows == 0) {
    echo "<div class='success'>✅ No orphaned consultations found</div>";
    exit;
}

echo "<div class='error'>⚠️ Found {$orphaned->num_rows} orphaned paid consultation(s)</div>";

echo "<table>";
echo "<tr><th>ID</th><th>Patient</th><th>Symptoms</th><th>Current Status</th><th>Action</th></tr>";

$fixed_count = 0;

while ($cons = $orphaned->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$cons['id']}</td>";
    echo "<td>{$cons['patient_name']}</td>";
    echo "<td>" . substr($cons['symptoms'], 0, 50) . "...</td>";
    echo "<td>{$cons['status']}</td>";
    
    // Assign to Emily and set status to pending
    $update = $conn->prepare("
        UPDATE consultations 
        SET doctor_id = ?, 
            status = 'pending',
            consultation_fee = 500,
            updated_at = NOW()
        WHERE id = ?
    ");
    $update->bind_param("ii", $emily_id, $cons['id']);
    
    if ($update->execute()) {
        echo "<td style='background: #d1fae5'>✅ Assigned to Emily, status = pending</td>";
        $fixed_count++;
    } else {
        echo "<td style='background: #fee2e2'>❌ Failed to update</td>";
    }
    
    echo "</tr>";
}

echo "</table>";

if ($fixed_count > 0) {
    echo "<div class='success'>✅ Successfully fixed $fixed_count consultation(s)</div>";
    echo "<div class='info'>💡 These consultations should now appear in Emily's Incoming Requests</div>";
    echo "<p><a href='doctor_dashboard.php' style='background: #0d9488; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; display: inline-block; margin-top: 20px;'>Go to Emily's Dashboard</a></p>";
} else {
    echo "<div class='error'>❌ No consultations were fixed</div>";
}

// Show what should appear now
echo "<h3>After Fix - Emily's Incoming Requests Should Show:</h3>";
$should_show = $conn->query("
    SELECT c.id, u.full_name as patient_name, c.status, c.payment_status, c.created_at
    FROM consultations c
    JOIN users u ON c.patient_id = u.id
    WHERE c.doctor_id = $emily_id
      AND c.status IN ('pending', 'assigned')
      AND c.payment_status = 'paid'
    ORDER BY c.created_at DESC
");

if ($should_show->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Patient</th><th>Status</th><th>Payment</th><th>Created</th></tr>";
    while ($r = $should_show->fetch_assoc()) {
        echo "<tr style='background: #d1fae5'>";
        echo "<td>{$r['id']}</td>";
        echo "<td>{$r['patient_name']}</td>";
        echo "<td>{$r['status']}</td>";
        echo "<td>{$r['payment_status']}</td>";
        echo "<td>" . date('Y-m-d H:i', strtotime($r['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ Still no consultations to show. Check if payment_status = 'paid'</div>";
}
