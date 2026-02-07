<?php
session_start();
require_once 'db.php';

echo "<h1>Pharmacy API Debug</h1>";

// Check session
echo "<h2>1. Session Info</h2>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'NOT SET') . "<br>";
echo "Email: " . ($_SESSION['email'] ?? 'NOT SET') . "<br><br>";

if (!isset($_SESSION['user_id'])) {
    echo "❌ <strong>NOT LOGGED IN!</strong><br>";
    echo "You need to login as pharmacy first!<br><br>";
}

$userId = $_SESSION['user_id'] ?? null;

if ($userId) {
    // Check user details
    $user = $conn->query("SELECT * FROM users WHERE id = $userId")->fetch_assoc();
    echo "<h2>2. Logged In User</h2>";
    echo "ID: {$user['id']}<br>";
    echo "Email: {$user['email']}<br>";
    echo "Role: {$user['role']}<br><br>";
    
    // Check if this is the pharmacy user
    if ($user['email'] === 'central.pharmacy@medconnect.com') {
        echo "✅ Logged in as Central Pharmacy<br><br>";
    } else {
        echo "⚠️ <strong>NOT logged in as pharmacy!</strong><br>";
        echo "Current user: {$user['email']}<br>";
        echo "Expected: central.pharmacy@medconnect.com<br><br>";
    }
    
    // Run the exact query from pharmacy_api.php
    echo "<h2>3. Pharmacy API Query Result</h2>";
    echo "Running query with pharmacy_id = $userId<br><br>";
    
    $stmt = $conn->prepare("
        SELECT p.*, 
               c.symptoms, c.diagnosis as consultation_diagnosis,
               pat.full_name as patient_name, pat.email as patient_email,
               doc.full_name as doctor_name,
               dp.specialization
        FROM prescriptions_v2 p
        JOIN consultations c ON p.consultation_id = c.id
        JOIN users pat ON p.patient_id = pat.id
        JOIN users doc ON p.doctor_id = doc.id
        LEFT JOIN doctor_profiles dp ON doc.id = dp.user_id
        WHERE p.pharmacy_id = ? AND p.status = 'sent_to_pharmacy'
        ORDER BY p.sent_to_pharmacy_at DESC
    ");
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "Found: <strong>{$result->num_rows}</strong> prescriptions<br><br>";
    
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Prescription #</th><th>Patient</th><th>Doctor</th><th>Status</th><th>Pharmacy ID</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['prescription_number']}</td>";
            echo "<td>{$row['patient_name']}</td>";
            echo "<td>{$row['doctor_name']}</td>";
            echo "<td><strong>{$row['status']}</strong></td>";
            echo "<td>{$row['pharmacy_id']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ No prescriptions found!<br><br>";
        
        // Debug: Show all prescriptions with sent_to_pharmacy status
        echo "<h3>All Prescriptions with 'sent_to_pharmacy' status:</h3>";
        $all = $conn->query("
            SELECT id, prescription_number, pharmacy_id, status, consultation_id
            FROM prescriptions_v2
            WHERE status = 'sent_to_pharmacy'
        ");
        
        if ($all->num_rows > 0) {
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><th>ID</th><th>Prescription #</th><th>Pharmacy ID</th><th>Status</th><th>Consultation ID</th></tr>";
            while ($row = $all->fetch_assoc()) {
                $highlight = ($row['pharmacy_id'] == $userId) ? "background: yellow;" : "";
                echo "<tr style='$highlight'>";
                echo "<td>{$row['id']}</td>";
                echo "<td>{$row['prescription_number']}</td>";
                echo "<td>{$row['pharmacy_id']}</td>";
                echo "<td>{$row['status']}</td>";
                echo "<td>{$row['consultation_id']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<br><small>Yellow = matches your pharmacy_id ($userId)</small>";
        } else {
            echo "No prescriptions with 'sent_to_pharmacy' status found!";
        }
    }
}

echo "<br><br><a href='pharmacy_dashboard.php'>Back to Pharmacy Dashboard</a>";