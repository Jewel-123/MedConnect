<?php
/**
 * Verify Doctor Mapping - Automated Verification Script
 * Tests the complete consultation booking flow
 */

require_once 'db.php';

echo "<h1>Doctor Mapping Verification</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    h1 { color: #0d9488; }
    h2 { color: #0f766e; margin-top: 30px; }
    .success { background: #d1fae5; padding: 15px; border-left: 4px solid #10b981; margin: 10px 0; }
    .error { background: #fee2e2; padding: 15px; border-left: 4px solid #ef4444; margin: 10px 0; }
    .warning { background: #fef3c7; padding: 15px; border-left: 4px solid #f59e0b; margin: 10px 0; }
    .info { background: #e0f2fe; padding: 15px; border-left: 4px solid #0284c7; margin: 10px 0; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
    th { background: #f9fafb; font-weight: 600; }
    pre { background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 8px; overflow-x: auto; }
</style>";

echo "<h2>1. Checking Doctor Availability</h2>";

// Check for approved doctors
$doctors = $conn->query("
    SELECT u.id, u.full_name, u.email, dp.specialization, dp.consultation_fee, u.status
    FROM users u
    INNER JOIN doctor_profiles dp ON u.id = dp.user_id
    WHERE u.role = 'doctor'
    ORDER BY u.status DESC, dp.specialization
");

if ($doctors->num_rows > 0) {
    echo "<div class='success'>✓ Found " . $doctors->num_rows . " doctors in the system</div>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Specialization</th><th>Fee</th><th>Status</th></tr>";
    while ($doc = $doctors->fetch_assoc()) {
        $statusClass = $doc['status'] === 'approved' ? 'success' : 'warning';
        echo "<tr>";
        echo "<td>{$doc['id']}</td>";
        echo "<td>{$doc['full_name']}</td>";
        echo "<td>{$doc['email']}</td>";
        echo "<td>{$doc['specialization']}</td>";
        echo "<td>₹{$doc['consultation_fee']}</td>";
        echo "<td><span style='background:#" . ($doc['status'] === 'approved' ? 'd1fae5' : 'fef3c7') . ";padding:4px 8px;border-radius:4px'>{$doc['status']}</span></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>✗ No doctors found in the system</div>";
}

echo "<h2>2. Recent Consultations Analysis</h2>";

// Check consultations created in last 24 hours
$consultations = $conn->query("
    SELECT c.id, c.created_at, c.patient_id, c.doctor_id, c.consultation_fee, 
           c.payment_status, c.status, c.matched_specialty,
           u.full_name as patient_name, u.email as patient_email,
           d.full_name as doctor_name
    FROM consultations c
    INNER JOIN users u ON c.patient_id = u.id
    LEFT JOIN users d ON c.doctor_id = d.id
    WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY c.created_at DESC
    LIMIT 20
");

if ($consultations->num_rows > 0) {
    echo "<div class='info'>Found " . $consultations->num_rows . " consultations in the last 7 days</div>";
    
    $with_doctor = 0;
    $without_doctor = 0;
    $paid = 0;
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Created</th><th>Patient</th><th>Doctor</th><th>Specialty</th><th>Fee</th><th>Payment</th><th>Status</th></tr>";
    
    while ($cons = $consultations->fetch_assoc()) {
        if ($cons['doctor_id']) {
            $with_doctor++;
        } else {
            $without_doctor++;
        }
        
        if ($cons['payment_status'] === 'paid') {
            $paid++;
        }
        
        $doctorDisplay = $cons['doctor_name'] ?: '<span style="color:#ef4444;">NO DOCTOR</span>';
        $paymentBg = $cons['payment_status'] === 'paid' ? '#d1fae5' : '#fee2e2';
        
        echo "<tr>";
        echo "<td>{$cons['id']}</td>";
        echo "<td>" . date('Y-m-d H:i', strtotime($cons['created_at'])) . "</td>";
        echo "<td>{$cons['patient_name']}</td>";
        echo "<td>{$doctorDisplay}</td>";
        echo "<td>{$cons['matched_specialty']}</td>";
        echo "<td>₹" . ($cons['consultation_fee'] ?: 'N/A') . "</td>";
        echo "<td><span style='background:{$paymentBg};padding:4px 8px;border-radius:4px'>{$cons['payment_status']}</span></td>";
        echo "<td>{$cons['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='info'>";
    echo "<strong>Summary:</strong><br>";
    echo "• Consultations WITH doctor assigned: <strong>{$with_doctor}</strong><br>";
    echo "• Consultations WITHOUT doctor (BROKEN): <strong style='color:#ef4444'>{$without_doctor}</strong><br>";
    echo "• Paid consultations: <strong>{$paid}</strong>";
    echo "</div>";
    
    if ($without_doctor > 0) {
        echo "<div class='warning'>⚠️ Found {$without_doctor} consultations without a doctor_id. These won't appear in doctor dashboards.</div>";
    } else {
        echo "<div class='success'>✓ All recent consultations have doctor_id assigned!</div>";
    }
} else {
    echo "<div class='warning'>No consultations found in the last 7 days</div>";
}

echo "<h2>3. Doctor Dashboard Query Test</h2>";

// Test the doctor dashboard query for each doctor
$doctors = $conn->query("SELECT id, full_name FROM users WHERE role = 'doctor' AND status = 'approved' LIMIT 5");

while ($doctor = $doctors->fetch_assoc()) {
    $doctor_id = $doctor['id'];
    $doctor_name = $doctor['full_name'];
    
    $incoming = $conn->query("
        SELECT COUNT(*) as count FROM consultations 
        WHERE doctor_id = $doctor_id 
        AND status IN ('pending', 'assigned')
        AND payment_status = 'paid'
    ");
    
    $count = $incoming->fetch_assoc()['count'];
    
    echo "<div class='info'>";
    echo "<strong>Dr. {$doctor_name}</strong> (ID: {$doctor_id})<br>";
    echo "Incoming requests (visible in dashboard): <strong>{$count}</strong>";
    echo "</div>";
}

echo "<h2>4. Payment Flow Check</h2>";

// Check if payment_api.php correctly updates consultations
$recent_payments = $conn->query("
    SELECT pt.*, c.doctor_id, c.consultation_fee
    FROM payment_transactions pt
    LEFT JOIN consultations c ON pt.related_id = c.id AND pt.transaction_type = 'consultation_fee'
    WHERE pt.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    AND pt.transaction_type = 'consultation_fee'
    ORDER BY pt.created_at DESC
    LIMIT 10
");

if ($recent_payments->num_rows > 0) {
    echo "<div class='info'>Found " . $recent_payments->num_rows . " consultation payments in the last 7 days</div>";
    echo "<table>";
    echo "<tr><th>Transaction ID</th><th>Consultation ID</th><th>Amount</th><th>Status</th><th>Created</th><th>Doctor Assigned?</th></tr>";
    
    while ($payment = $recent_payments->fetch_assoc()) {
        $doctorStatus = $payment['doctor_id'] ? '<span style="color:#10b981">✓ YES</span>' : '<span style="color:#ef4444">✗ NO</span>';
        echo "<tr>";
        echo "<td>{$payment['id']}</td>";
        echo "<td>{$payment['related_id']}</td>";
        echo "<td>₹{$payment['amount']}</td>";
        echo "<td>{$payment['status']}</td>";
        echo "<td>" . date('Y-m-d H:i', strtotime($payment['created_at'])) . "</td>";
        echo "<td>{$doctorStatus}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='warning'>No consultation payments found in the last 7 days</div>";
}

echo "<h2>5. Recommendations</h2>";

// Provide recommendations based on findings
$issues = [];
$recommendations = [];

// Check for consultations without doctor_id
$orphaned = $conn->query("SELECT COUNT(*) as count FROM consultations WHERE doctor_id IS NULL");
$orphaned_count = $orphaned->fetch_assoc()['count'];

if ($orphaned_count > 0) {
    $issues[] = "Found {$orphaned_count} consultations without doctor_id";
    $recommendations[] = "Clean up old consultations or assign them to doctors manually";
}

// Check for paid consultations without doctor_id
$paid_orphaned = $conn->query("SELECT COUNT(*) as count FROM consultations WHERE doctor_id IS NULL AND payment_status = 'paid'");
$paid_orphaned_count = $paid_orphaned->fetch_assoc()['count'];

if ($paid_orphaned_count > 0) {
    $issues[] = "CRITICAL: Found {$paid_orphaned_count} PAID consultations without doctor_id";
    $recommendations[] = "Urgently assign these paid consultations to doctors";
}

if (count($issues) > 0) {
    echo "<div class='error'><strong>Issues Found:</strong><ul>";
    foreach ($issues as $issue) {
        echo "<li>{$issue}</li>";
    }
    echo "</ul></div>";
    
    echo "<div class='warning'><strong>Recommendations:</strong><ul>";
    foreach ($recommendations as $rec) {
        echo "<li>{$rec}</li>";
    }
    echo "</ul></div>";
} else {
    echo "<div class='success'>✓ No critical issues found! The system is properly mapping consultations to doctors.</div>";
}

echo "<h2>6. New Flow Verification</h2>";
echo "<div class='info'>";
echo "<strong>Expected Flow (NEW):</strong><br>";
echo "1. Patient submits symptoms via symptom_checker.php<br>";
echo "2. NLP analyzer determines specialty<br>";
echo "3. get_doctors_by_specialty.php returns matching doctors<br>";
echo "4. Patient selects a doctor<br>";
echo "5. symptom_intake_api.php (assign_doctor action) updates consultation with doctor_id + fee<br>";
echo "6. Patient is redirected to payment_gateway.php<br>";
echo "7. After payment, consultation has doctor_id and payment_status = 'paid'<br>";
echo "8. Doctor sees consultation in their dashboard";
echo "</div>";

echo "<p style='margin-top: 40px; color: #64748b; font-size: 14px;'>Verification completed at " . date('Y-m-d H:i:s') . "</p>";
