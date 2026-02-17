<?php
require_once 'db.php';

echo "<h2>🔧 Fix Consultations 102-107</h2>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
table { width: 100%; border-collapse: collapse; background: white; margin: 20px 0; }
th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
th { background: #0d9488; color: white; }
.success { background: #d1fae5; padding: 15px; border-left: 4px solid #10b981; margin: 10px 0; }
.error { background: #fee2e2; padding: 15px; border-left: 4px solid #ef4444; margin: 10px 0; }
button { background: #ef4444; color: white; padding: 15px 30px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: bold; }
button:hover { background: #dc2626; }
</style>";

$emily_id = 25;
$consultation_ids = '102,103,104,105,106,107';

echo "<div class='error'>";
echo "<p><strong>Problem:</strong> Consultations 102-107 have doctor_id = NULL</p>";
echo "<p>They need to be assigned to Emily (ID: 25)</p>";
echo "</div>";

if (!isset($_POST['fix_now'])) {
    echo "<form method='POST'>";
    echo "<input type='hidden' name='fix_now' value='1'>";
    echo "<p><strong>This will:</strong></p>";
    echo "<ul>";
    echo "<li>Set doctor_id = 25 (Emily)</li>";
    echo "<li>Set status = 'pending'</li>";
    echo "<li>Set payment_status = 'paid'</li>";
    echo "<li>Set consultation_fee = 500</li>";
    echo "</ul>";
    echo "<button type='submit'>🔧 FIX NOW - Assign to Emily</button>";
    echo "</form>";
} else {
    // Execute fix
    $result = $conn->query("
        UPDATE consultations
        SET doctor_id = $emily_id,
            status = 'pending',
            payment_status = 'paid',
            consultation_fee = 500,
            updated_at = NOW()
        WHERE id IN ($consultation_ids)
    ");
    
    if ($result) {
        $affected = $conn->affected_rows;
        echo "<div class='success'>";
        echo "<h2>✅ SUCCESS!</h2>";
        echo "<p>Fixed $affected consultation(s)</p>";
        echo "<p>They should now appear in Emily's Incoming Requests!</p>";
        echo "</div>";
        
        // Verify
        echo "<h3>Verification:</h3>";
        $verify = $conn->query("
            SELECT c.id, c.doctor_id, c.status, c.payment_status, c.consultation_fee,
                   d.full_name as doctor_name
            FROM consultations c
            LEFT JOIN users d ON c.doctor_id = d.id
            WHERE c.id IN ($consultation_ids)
        ");
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Doctor ID</th><th>Doctor Name</th><th>Status</th><th>Payment</th><th>Fee</th></tr>";
        while ($v = $verify->fetch_assoc()) {
            echo "<tr style='background: #d1fae5'>";
            echo "<td>{$v['id']}</td>";
            echo "<td>{$v['doctor_id']}</td>";
            echo "<td>{$v['doctor_name']}</td>";
            echo "<td>{$v['status']}</td>";
            echo "<td>{$v['payment_status']}</td>";
            echo "<td>₹{$v['consultation_fee']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check Emily's dashboard
        echo "<h3>Emily's Incoming Requests Now Shows:</h3>";
        $emily_incoming = $conn->query("
            SELECT c.id, u.full_name as patient_name, c.symptoms, c.created_at
            FROM consultations c
            JOIN users u ON c.patient_id = u.id
            WHERE c.doctor_id = $emily_id
              AND c.status IN ('pending', 'assigned')
            ORDER BY c.created_at DESC
        ");
        
        echo "<div class='success'><strong>{$emily_incoming->num_rows} consultation(s) in Emily's incoming requests</strong></div>";
        
        if ($emily_incoming->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Patient</th><th>Symptoms</th><th>Created</th></tr>";
            while ($r = $emily_incoming->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$r['id']}</td>";
                echo "<td>{$r['patient_name']}</td>";
                echo "<td>" . substr($r['symptoms'], 0, 50) . "...</td>";
                echo "<td>" . date('Y-m-d H:i:s', strtotime($r['created_at'])) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        echo "<p style='margin-top: 20px;'>";
        echo "<a href='doctor_dashboard.php' style='background:#0d9488;color:white;padding:12px 24px;text-decoration:none;border-radius:6px;display:inline-block;'>📊 Go to Emily's Dashboard</a>";
        echo "</p>";
        
    } else {
        echo "<div class='error'>❌ Fix failed: " . $conn->error . "</div>";
    }
}

echo "<h3>🔍 Why This Happened:</h3>";
echo "<div class='error'>";
echo "<p>Consultations are still being created with doctor_id = NULL</p>";
echo "<p>This means the symptom intake flow is not assigning doctors properly.</p>";
echo "<p><strong>Next Step:</strong> I need to investigate which code is creating these consultations.</p>";
echo "</div>";
