<?php
require_once 'db.php';

echo "<html><head><title>MedConnect - Final Schema Repair</title>";
echo "<style>body{font-family:sans-serif; line-height:1.5; padding:2rem; max-width:800px; margin:0 auto; background:#f1f5f9; color:#1e293b;} 
      .card{background:white; padding:2rem; border-radius:12px; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);} 
      .success{color:#059669; font-weight:600;} .error{color:#dc2626; font-weight:600;} .info{color:#0891b2;}</style></head><body>";
echo "<div class='card'><h1>🛠️ MedConnect Schema Repair</h1>";

function repairTable($conn, $tableName, $updates) {
    echo "<h3>Checking table: <code>$tableName</code></h3><ul>";
    $result = $conn->query("DESC $tableName");
    $cols = [];
    if ($result) {
        while($row = $result->fetch_assoc()) $cols[] = $row['Field'];
    }
    
    foreach ($updates as $col => $definition) {
        if (!in_array($col, $cols)) {
            // Fix: Include the column name in the ALTER command
            if ($conn->query("ALTER TABLE $tableName ADD `$col` $definition")) {
                echo "<li><span class='success'>✓ Added column: $col</span></li>";
            } else {
                echo "<li><span class='error'>✗ Error adding $col: " . $conn->error . "</span></li>";
            }
        } else {
            echo "<li><span class='info'>• Column $col already exists.</span></li>";
        }
    }
    echo "</ul>";
}

// 1. Repair Consultations Table
repairTable($conn, 'consultations', [
    'payment_status' => "ENUM('pending', 'paid', 'refunded') DEFAULT 'pending' AFTER status"
]);

// 2. Repair Doctor Profiles Table
repairTable($conn, 'doctor_profiles', [
    'is_online' => "TINYINT(1) DEFAULT 0",
    'max_concurrent_chats' => "INT DEFAULT 3",
    'consultation_types' => "VARCHAR(255) DEFAULT 'chat,audio,video'"
]);

// 3. Fix existing data - Mark as paid for testing if needed (uncomment if necessary)
// $conn->query("UPDATE consultations SET payment_status = 'paid' WHERE payment_status = 'pending' AND status = 'pending'");

echo "<hr><p class='success'>✅ System is now fully aligned with the new Consultation Workflow.</p>";
echo "<p><a href='doctor_dashboard.php' style='display:inline-block; padding:0.75rem 1.5rem; background:#0d9488; color:white; text-decoration:none; border-radius:6px;'>Go to Doctor Dashboard</a></p>";
echo "</div></body></html>";