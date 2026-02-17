<?php
require_once 'db.php';

echo "<h2>🔍 Payment vs Consultation Mismatch</h2>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
table { width: 100%; border-collapse: collapse; background: white; margin: 20px 0; }
th, td { padding: 10px; border: 1px solid #ddd; text-align: left; font-size: 12px; }
th { background: #0d9488; color: white; }
.success { background: #d1fae5; padding: 15px; border-left: 4px solid #10b981; margin: 10px 0; }
.error { background: #fee2e2; padding: 15px; border-left: 4px solid #ef4444; margin: 10px 0; }
.warning { background: #fef3c7; padding: 15px; border-left: 4px solid #f59e0b; margin: 10px 0; }
</style>";

$patient_email = 'jewelbiju2028@mca.ajce.in';
$patient = $conn->query("SELECT id FROM users WHERE email = '$patient_email'")->fetch_assoc();
$patient_id = $patient['id'];

echo "<h3>1. Your Recent Payments</h3>";
$payments = $conn->query("
    SELECT * FROM payment_transactions
    WHERE user_id = $patient_id
      AND transaction_type = 'consultation_fee'
      AND created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
    ORDER BY created_at DESC
");

$missing_consultations = [];
$existing_consultations = [];

if ($payments->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Payment ID</th><th>Consultation ID</th><th>Amount</th><th>Status</th><th>Created</th><th>Consultation Exists?</th></tr>";
    
    while ($p = $payments->fetch_assoc()) {
        $cons_id = $p['related_id'];
        
        // Check if consultation exists
        $exists = $conn->query("SELECT id FROM consultations WHERE id = $cons_id")->num_rows > 0;
        
        $color = $exists ? '#d1fae5' : '#fee2e2';
        
        echo "<tr style='background: $color'>";
        echo "<td>{$p['id']}</td>";
        echo "<td>{$cons_id}</td>";
        echo "<td>₹{$p['amount']}</td>";
        echo "<td>{$p['status']}</td>";
        echo "<td>" . date('Y-m-d H:i:s', strtotime($p['created_at'])) . "</td>";
        
        if ($exists) {
            echo "<td style='color: green; font-weight: bold;'>✅ YES</td>";
            $existing_consultations[] = $cons_id;
        } else {
            echo "<td style='color: red; font-weight: bold;'>❌ DELETED/MISSING</td>";
            $missing_consultations[] = [
                'id' => $cons_id,
                'amount' => $p['amount'],
                'payment_id' => $p['id'],
                'created' => $p['created_at']
            ];
        }
        
        echo "</tr>";
    }
    echo "</table>";
}

if (count($missing_consultations) > 0) {
    echo "<div class='error'>";
    echo "<h2>⚠️ PROBLEM FOUND!</h2>";
    echo "<p><strong>" . count($missing_consultations) . " consultations are missing from the database!</strong></p>";
    echo "<p>You paid for these consultations, but the consultation records were deleted or never created.</p>";
    echo "</div>";
    
    echo "<h3>2. Missing Consultations:</h3>";
    echo "<table>";
    echo "<tr><th>Consultation ID</th><th>Payment ID</th><th>Amount Paid</th><th>Payment Date</th></tr>";
    foreach ($missing_consultations as $mc) {
        echo "<tr style='background: #fee2e2'>";
        echo "<td>{$mc['id']}</td>";
        echo "<td>{$mc['payment_id']}</td>";
        echo "<td>₹{$mc['amount']}</td>";
        echo "<td>" . date('Y-m-d H:i:s', strtotime($mc['created'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='warning'>";
    echo "<h3>What Happened?</h3>";
    echo "<p>Most likely scenario:</p>";
    echo "<ul>";
    echo "<li>When you submitted symptoms through the symptom checker, consultations were created</li>";
    echo "<li>You completed payments successfully</li>";
    echo "<li>But then the consultations were deleted (maybe during testing or code changes)</li>";
    echo "<li>The payments remain in the system, but the consultation records are gone</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>Solution:</h3>";
    echo "<p><strong>You need to create NEW consultations</strong> through the proper flow:</p>";
    echo "<ol>";
    echo "<li>Go to <strong>Symptom Checker</strong></li>";
    echo "<li>Submit your symptoms</li>";
    echo "<li>Proceed to <strong>Appointment Booking</strong></li>";
    echo "<li>Select Emily (or your preferred doctor)</li>";
    echo "<li>Complete payment</li>";
    echo "</ol>";
    echo "<p>The new flow I implemented will ensure consultations are created correctly with doctor_id assigned.</p>";
    echo "</div>";
}

if (count($existing_consultations) > 0) {
    echo "<h3>3. Existing Consultations (These Ones ARE in Database):</h3>";
    $ids = implode(',', $existing_consultations);
    
    $cons = $conn->query("
        SELECT c.*, d.full_name as doctor_name
        FROM consultations c
        LEFT JOIN users d ON c.doctor_id = d.id
        WHERE c.id IN ($ids)
    ");
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Doctor</th><th>Status</th><th>Payment Status</th><th>Symptoms</th></tr>";
    while ($c = $cons->fetch_assoc()) {
        $color = $c['doctor_id'] ? '#d1fae5' : '#fee2e2';
        echo "<tr style='background: $color'>";
        echo "<td>{$c['id']}</td>";
        echo "<td>" . ($c['doctor_name'] ?: '<span style="color:red">NULL</span>') . "</td>";
        echo "<td>{$c['status']}</td>";
        echo "<td>{$c['payment_status']}</td>";
        echo "<td>" . substr($c['symptoms'], 0, 40) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>Summary:</h3>";
echo "<ul>";
echo "<li>✅ Payments: <strong>{$payments->num_rows} found</strong></li>";
echo "<li>❌ Missing Consultations: <strong>" . count($missing_consultations) . "</strong></li>";
echo "<li>✅ Existing Consultations: <strong>" . count($existing_consultations) . "</strong></li>";
echo "</ul>";
