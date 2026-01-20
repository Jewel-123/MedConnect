<?php
/**
 * Simple Doctor Dashboard Schema Installer
 * This creates all tables directly without complex prepared statements
 */

require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Install Doctor Dashboard Schema</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #0ea5e9; margin-bottom: 20px; }
        .success { color: #10b981; padding: 10px; background: #d1fae5; border-left: 4px solid #10b981; margin: 10px 0; }
        .error { color: #ef4444; padding: 10px; background: #fee2e2; border-left: 4px solid #ef4444; margin: 10px 0; }
        .info { color: #0ea5e9; padding: 10px; background: #e0f2fe; border-left: 4px solid #0ea5e9; margin: 10px 0; }
        .warning { color: #f59e0b; padding: 10px; background: #fef3c7; border-left: 4px solid #f59e0b; margin: 10px 0; }
        pre { background: #f8fafc; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        .btn { display: inline-block; padding: 12px 24px; background: #0ea5e9; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; }
        .btn:hover { background: #0284c7; }
        .step { margin: 20px 0; padding: 15px; background: #f8fafc; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏥 Doctor Dashboard Schema Installation</h1>
        
<?php

$errors = [];
$success = [];

// Step 1: Add columns to consultations table
echo "<div class='step'><h3>Step 1: Extending Consultations Table</h3>";

$columns_to_add = [
    ['name' => 'doctor_id', 'definition' => 'INT DEFAULT NULL', 'after' => 'patient_id'],
    ['name' => 'consultation_mode', 'definition' => "ENUM('video', 'audio', 'chat') DEFAULT 'chat'", 'after' => 'status'],
    ['name' => 'language_preference', 'definition' => "VARCHAR(50) DEFAULT 'English'", 'after' => 'consultation_mode'],
    ['name' => 'urgency_score', 'definition' => 'INT DEFAULT 50', 'after' => 'severity'],
    ['name' => 'assigned_at', 'definition' => 'TIMESTAMP NULL DEFAULT NULL', 'after' => 'updated_at'],
    ['name' => 'completed_at', 'definition' => 'TIMESTAMP NULL DEFAULT NULL', 'after' => 'assigned_at']
];

foreach ($columns_to_add as $col) {
    $check = $conn->query("SHOW COLUMNS FROM consultations LIKE '{$col['name']}'");
    if ($check && $check->num_rows > 0) {
        echo "<div class='info'>ℹ Column '{$col['name']}' already exists - skipped</div>";
    } else {
        $sql = "ALTER TABLE consultations ADD COLUMN {$col['name']} {$col['definition']} AFTER {$col['after']}";
        if ($conn->query($sql)) {
            echo "<div class='success'>✓ Added column '{$col['name']}'</div>";
            $success[] = "Added column {$col['name']}";
        } else {
            echo "<div class='error'>✗ Error adding column '{$col['name']}': " . $conn->error . "</div>";
            $errors[] = "Column {$col['name']}: " . $conn->error;
        }
    }
}

// Add foreign key
$fk_check = $conn->query("SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = 'medconnect' AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'doctor_id' AND REFERENCED_TABLE_NAME = 'users'");
if ($fk_check && $fk_check->num_rows > 0) {
    echo "<div class='info'>ℹ Foreign key already exists - skipped</div>";
} else {
    if ($conn->query("ALTER TABLE consultations ADD CONSTRAINT fk_consultations_doctor FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE SET NULL")) {
        echo "<div class='success'>✓ Added foreign key constraint</div>";
    } else {
        echo "<div class='warning'>⚠ Foreign key: " . $conn->error . "</div>";
    }
}

// Update status enum
if ($conn->query("ALTER TABLE consultations MODIFY COLUMN status ENUM('pending', 'assigned', 'in_progress', 'completed', 'cancelled', 'declined') DEFAULT 'pending'")) {
    echo "<div class='success'>✓ Updated status enum</div>";
} else {
    echo "<div class='warning'>⚠ Status enum: " . $conn->error . "</div>";
}

echo "</div>";

// Step 2: Create new tables
echo "<div class='step'><h3>Step 2: Creating New Tables</h3>";

$tables = [
    'consultation_sessions' => "CREATE TABLE IF NOT EXISTS consultation_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        consultation_id INT NOT NULL,
        session_token VARCHAR(255) NOT NULL,
        session_type ENUM('video', 'audio', 'chat') NOT NULL,
        started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ended_at TIMESTAMP NULL DEFAULT NULL,
        duration_minutes INT DEFAULT 0,
        transcription TEXT DEFAULT NULL,
        ai_highlights JSON DEFAULT NULL,
        encryption_key_hash VARCHAR(255) DEFAULT NULL,
        FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE CASCADE,
        INDEX idx_consultation_id (consultation_id),
        INDEX idx_session_token (session_token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'prescriptions_v2' => "CREATE TABLE IF NOT EXISTS prescriptions_v2 (
        id INT AUTO_INCREMENT PRIMARY KEY,
        consultation_id INT NOT NULL,
        patient_id INT NOT NULL,
        doctor_id INT NOT NULL,
        pharmacy_id INT DEFAULT NULL,
        icd_code VARCHAR(20) DEFAULT NULL,
        diagnosis TEXT NOT NULL,
        follow_up_date DATE DEFAULT NULL,
        notes_for_patient TEXT DEFAULT NULL,
        notes_for_pharmacy TEXT DEFAULT NULL,
        status ENUM('draft', 'issued', 'sent_to_pharmacy', 'filled', 'cancelled') DEFAULT 'draft',
        sent_at TIMESTAMP NULL DEFAULT NULL,
        filled_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE CASCADE,
        FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (pharmacy_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_consultation_id (consultation_id),
        INDEX idx_patient_id (patient_id),
        INDEX idx_doctor_id (doctor_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'prescription_items_v2' => "CREATE TABLE IF NOT EXISTS prescription_items_v2 (
        id INT AUTO_INCREMENT PRIMARY KEY,
        prescription_id INT NOT NULL,
        medicine_name VARCHAR(200) NOT NULL,
        dosage VARCHAR(100) NOT NULL,
        frequency VARCHAR(100) NOT NULL,
        duration VARCHAR(50) NOT NULL,
        instructions TEXT DEFAULT NULL,
        quantity INT DEFAULT 1,
        FOREIGN KEY (prescription_id) REFERENCES prescriptions_v2(id) ON DELETE CASCADE,
        INDEX idx_prescription_id (prescription_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'prescription_tests' => "CREATE TABLE IF NOT EXISTS prescription_tests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        prescription_id INT NOT NULL,
        test_type ENUM('lab_test', 'imaging', 'referral') NOT NULL,
        test_name VARCHAR(200) NOT NULL,
        instructions TEXT DEFAULT NULL,
        urgency ENUM('routine', 'urgent', 'stat') DEFAULT 'routine',
        FOREIGN KEY (prescription_id) REFERENCES prescriptions_v2(id) ON DELETE CASCADE,
        INDEX idx_prescription_id (prescription_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'doctor_reviews' => "CREATE TABLE IF NOT EXISTS doctor_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        doctor_id INT NOT NULL,
        patient_id INT NOT NULL,
        consultation_id INT DEFAULT NULL,
        rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
        review_text TEXT DEFAULT NULL,
        doctor_response TEXT DEFAULT NULL,
        quality_flag ENUM('none', 'pending_review', 'flagged') DEFAULT 'none',
        admin_notes TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        responded_at TIMESTAMP NULL DEFAULT NULL,
        FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE SET NULL,
        INDEX idx_doctor_id (doctor_id),
        INDEX idx_patient_id (patient_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'doctor_availability' => "CREATE TABLE IF NOT EXISTS doctor_availability (
        id INT AUTO_INCREMENT PRIMARY KEY,
        doctor_id INT NOT NULL,
        day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        is_available BOOLEAN DEFAULT TRUE,
        auto_booking_enabled BOOLEAN DEFAULT TRUE,
        max_consultations_per_slot INT DEFAULT 1,
        FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_doctor_id (doctor_id),
        INDEX idx_day_of_week (day_of_week)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'doctor_availability_overrides' => "CREATE TABLE IF NOT EXISTS doctor_availability_overrides (
        id INT AUTO_INCREMENT PRIMARY KEY,
        doctor_id INT NOT NULL,
        override_date DATE NOT NULL,
        start_time TIME DEFAULT NULL,
        end_time TIME DEFAULT NULL,
        is_available BOOLEAN NOT NULL,
        reason VARCHAR(200) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_doctor_id (doctor_id),
        INDEX idx_override_date (override_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'doctor_earnings' => "CREATE TABLE IF NOT EXISTS doctor_earnings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        doctor_id INT NOT NULL,
        consultation_id INT NOT NULL,
        gross_amount DECIMAL(10, 2) NOT NULL,
        platform_commission_percent DECIMAL(5, 2) DEFAULT 10.00,
        platform_commission_amount DECIMAL(10, 2) NOT NULL,
        net_amount DECIMAL(10, 2) NOT NULL,
        payment_status ENUM('pending', 'processed', 'paid', 'on_hold') DEFAULT 'pending',
        payment_date DATE DEFAULT NULL,
        invoice_number VARCHAR(50) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE CASCADE,
        INDEX idx_doctor_id (doctor_id),
        INDEX idx_consultation_id (consultation_id),
        INDEX idx_payment_status (payment_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'consultation_audit_log' => "CREATE TABLE IF NOT EXISTS consultation_audit_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        consultation_id INT NOT NULL,
        doctor_id INT NOT NULL,
        action VARCHAR(100) NOT NULL,
        action_details JSON DEFAULT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        user_agent TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE CASCADE,
        FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_consultation_id (consultation_id),
        INDEX idx_doctor_id (doctor_id),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'consent_logs' => "CREATE TABLE IF NOT EXISTS consent_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        doctor_id INT NOT NULL,
        consultation_id INT DEFAULT NULL,
        consent_type ENUM('data_access', 'video_recording', 'data_sharing', 'prescription') NOT NULL,
        consent_given BOOLEAN NOT NULL,
        consent_text TEXT NOT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE SET NULL,
        INDEX idx_patient_id (patient_id),
        INDEX idx_doctor_id (doctor_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'doctor_notifications' => "CREATE TABLE IF NOT EXISTS doctor_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        doctor_id INT NOT NULL,
        notification_type ENUM('new_consultation', 'follow_up_due', 'pharmacy_query', 'review_received', 'system') NOT NULL,
        title VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        related_id INT DEFAULT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_doctor_id (doctor_id),
        INDEX idx_is_read (is_read),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'patient_medical_history' => "CREATE TABLE IF NOT EXISTS patient_medical_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        consultation_id INT DEFAULT NULL,
        doctor_id INT DEFAULT NULL,
        record_type ENUM('diagnosis', 'procedure', 'allergy', 'medication', 'condition') NOT NULL,
        record_title VARCHAR(200) NOT NULL,
        record_details TEXT DEFAULT NULL,
        record_date DATE NOT NULL,
        is_chronic BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE SET NULL,
        FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_patient_id (patient_id),
        INDEX idx_record_type (record_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

foreach ($tables as $table_name => $create_sql) {
    $check = $conn->query("SHOW TABLES LIKE '$table_name'");
    if ($check && $check->num_rows > 0) {
        echo "<div class='info'>ℹ Table '$table_name' already exists - skipped</div>";
    } else {
        if ($conn->query($create_sql)) {
            echo "<div class='success'>✓ Created table '$table_name'</div>";
            $success[] = "Created table $table_name";
        } else {
            echo "<div class='error'>✗ Error creating table '$table_name': " . $conn->error . "</div>";
            $errors[] = "Table $table_name: " . $conn->error;
        }
    }
}

echo "</div>";

// Summary
echo "<div class='step'><h3>📊 Installation Summary</h3>";
echo "<p><strong>Successful operations:</strong> " . count($success) . "</p>";
echo "<p><strong>Errors:</strong> " . count($errors) . "</p>";

if (count($errors) > 0) {
    echo "<div class='error'><strong>Errors encountered:</strong><ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul></div>";
} else {
    echo "<div class='success'><strong>✅ Installation completed successfully!</strong><br>All database tables have been created.</div>";
}

echo "</div>";

$conn->close();
?>

        <div style="text-align: center; margin-top: 30px;">
            <a href="test_doctor_dashboard.php" class="btn">Test Dashboard Access</a>
            <a href="doctor_dashboard.php" class="btn" style="background: #10b981;">Go to Doctor Dashboard</a>
        </div>
    </div>
</body>
</html>
