<?php
require_once 'db.php';

echo "<h2>🔍 Which Doctor Are Your Consultations Assigned To?</h2>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
table { width: 100%; border-collapse: collapse; background: white; margin: 20px 0; }
th, td { padding: 10px; border: 1px solid #ddd; text-align: left; font-size: 12px; }
th { background: #0d9488; color: white; }
.success { background: #d1fae5; padding: 15px; border-left: 4px solid #10b981; margin: 10px 0; }
.error { background: #fee2e2; padding: 15px; border-left: 4px solid #ef4444; margin: 10px 0; }
.info { background: #e0f2fe; padding: 15px; border-left: 4px solid #0284c7; margin: 10px 0; }
</style>";

// Check consultations 42-53
$consultations = $conn->query("
    SELECT c.id, c.doctor_id, c.status, c.payment_status, c.symptoms, c.matched_specialty,
           d.full_name as doctor_name, d.email as doctor_email
    FROM consultations c
    LEFT JOIN users d ON c.doctor_id = d.id AND d.role = 'doctor'
    WHERE c.id IN (42,43,44,45,46,47,48,49,50,51,52,53)
    ORDER BY c.id DESC
");

if ($consultations->num_rows == 0) {
    echo "<div class='error'>❌ No consultations found with IDs 42-53. They may have been deleted.</div>";
    exit;
}

echo "<h3>Your Paid Consultations:</h3>";
echo "<table>";
echo "<tr><th>ID</th><th>Doctor ID</th><th>Doctor Name</th><th>Status</th><th>Payment</th><th>Matched Specialty</th><th>Symptoms</th></tr>";

$by_doctor = [];
$null_doctor = [];

while ($c = $consultations->fetch_assoc()) {
    $doctor_id = $c['doctor_id'];
    $doctor_name = $c['doctor_name'] ?: 'NOT ASSIGNED';
    
    $color = $c['doctor_id'] ? '#d1fae5' : '#fee2e2';
    
    echo "<tr style='background: $color'>";
    echo "<td>{$c['id']}</td>";
    echo "<td>" . ($c['doctor_id'] ?: '<strong style="color:red">NULL</strong>') . "</td>";
    echo "<td><strong>$doctor_name</strong></td>";
    echo "<td>{$c['status']}</td>";
    echo "<td>{$c['payment_status']}</td>";
    echo "<td>" . ($c['matched_specialty'] ?: 'N/A') . "</td>";
    echo "<td>" . substr($c['symptoms'], 0, 40) . "...</td>";
    echo "</tr>";
    
    if ($c['doctor_id']) {
        if (!isset($by_doctor[$doctor_name])) {
            $by_doctor[$doctor_name] = ['id' => $doctor_id, 'count' => 0, 'consultations' => []];
        }
        $by_doctor[$doctor_name]['count']++;
        $by_doctor[$doctor_name]['consultations'][] = $c['id'];
    } else {
        $null_doctor[] = $c['id'];
    }
}

echo "</table>";

// Summary
echo "<h3>Summary:</h3>";

if (count($null_doctor) > 0) {
    echo "<div class='error'>";
    echo "<p><strong>" . count($null_doctor) . " consultation(s) have NO doctor assigned (doctor_id = NULL)</strong></p>";
    echo "<p>IDs: " . implode(', ', $null_doctor) . "</p>";
    echo "<p>❌ These won't appear in ANY doctor's dashboard!</p>";
    echo "</div>";
}

if (count($by_doctor) > 0) {
    echo "<div class='info'><strong>Consultations grouped by doctor:</strong></div>";
    foreach ($by_doctor as $doctor_name => $data) {
        echo "<div class='success'>";
        echo "<p><strong>$doctor_name</strong> (ID: {$data['id']})</p>";
        echo "<p>Has {$data['count']} consultation(s): " . implode(', ', $data['consultations']) . "</p>";
        echo "<p>✅ These should appear in {$doctor_name}'s dashboard</p>";
        echo "</div>";
    }
}

// Check all doctors
echo "<h3>Available Doctors:</h3>";
$doctors = $conn->query("SELECT id, full_name, email, specialization FROM users WHERE role = 'doctor' ORDER BY full_name");

echo "<table>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Specialization</th></tr>";
while ($d = $doctors->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$d['id']}</td>";
    echo "<td>{$d['full_name']}</td>";
    echo "<td>{$d['email']}</td>";
    echo "<td>" . ($d['specialization'] ?: 'N/A') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Action Required:</h3>";
echo "<div class='info'>";
echo "<p><strong>Question:</strong> Which doctor did you INTEND to book these consultations with?</p>";
echo "<p>The consultations will only appear in that specific doctor's dashboard.</p>";

if (count($null_doctor) > 0) {
    echo "<p style='margin-top:20px'><strong>For consultations with NULL doctor_id:</strong></p>";
    echo "<form method='POST'>";
    echo "<p>Select which doctor to assign them to:</p>";
    echo "<select name='target_doctor_id' style='padding:8px;border:1px solid #ddd;border-radius:4px;'>";
    
    $doctors2 = $conn->query("SELECT id, full_name FROM users WHERE role = 'doctor' ORDER BY full_name");
    while ($d = $doctors2->fetch_assoc()) {
        echo "<option value='{$d['id']}'>{$d['full_name']} (ID: {$d['id']})</option>";
    }
    
    echo "</select>";
    echo "<input type='hidden' name='assign_consultations' value='1'>";
    echo "<input type='hidden' name='consultation_ids' value='" . implode(',', $null_doctor) . "'>";
    echo "<br><br>";
    echo "<button type='submit' style='background:#0d9488;color:white;padding:12px 24px;border:none;border-radius:6px;cursor:pointer;font-size:14px;'>Assign to Selected Doctor</button>";
    echo "</form>";
}

echo "</div>";

// Handle assignment
if (isset($_POST['assign_consultations'])) {
    $doctor_id = intval($_POST['target_doctor_id']);
    $cons_ids = $_POST['consultation_ids'];
    
    $doctor_info = $conn->query("SELECT full_name FROM users WHERE id = $doctor_id")->fetch_assoc();
    
    $result = $conn->query("
        UPDATE consultations
        SET doctor_id = $doctor_id,
            status = 'pending',
            payment_status = 'paid',
            updated_at = NOW()
        WHERE id IN ($cons_ids)
    ");
    
    if ($result) {
        echo "<div class='success'>";
        echo "<h2>✅ SUCCESS!</h2>";
        echo "<p>Assigned {$conn->affected_rows} consultation(s) to <strong>{$doctor_info['full_name']}</strong></p>";
        echo "<p>They should now appear in {$doctor_info['full_name']}'s Incoming Requests!</p>";
        echo "<p><a href='doctor_dashboard.php' style='background:#0d9488;color:white;padding:10px 20px;text-decoration:none;border-radius:6px;display:inline-block;margin-top:10px;'>Go to Dashboard</a></p>";
        echo "</div>";
    }
}
