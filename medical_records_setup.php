<?php
require_once 'db.php';

echo "<h2>MedConnect Medical Records & Reminders — Database Setup</h2>";

// 1. Create medical_records table
$sql1 = "CREATE TABLE IF NOT EXISTS medical_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT DEFAULT NULL,
    diagnosis VARCHAR(255) NOT NULL,
    medications TEXT,
    allergies TEXT,
    lab_results TEXT,
    visit_date DATE NOT NULL,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql1) === TRUE) {
    echo "<p style='color:green;'>✅ Table 'medical_records' created or already exists.</p>";
} else {
    echo "<p style='color:red;'>❌ Error creating 'medical_records': " . $conn->error . "</p>";
}

// 2. Create reminders table
$sql2 = "CREATE TABLE IF NOT EXISTS reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medical_record_id INT DEFAULT NULL,
    patient_id INT NOT NULL,
    reminder_type ENUM('follow_up', 'medication_refill') NOT NULL,
    reminder_date DATE NOT NULL,
    notification_method ENUM('email', 'sms') NOT NULL,
    status ENUM('pending', 'sent', 'cancelled') DEFAULT 'pending',
    message TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (medical_record_id) REFERENCES medical_records(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql2) === TRUE) {
    echo "<p style='color:green;'>✅ Table 'reminders' created or already exists.</p>";
} else {
    echo "<p style='color:red;'>❌ Error creating 'reminders': " . $conn->error . "</p>";
}

// 3. Create reminder_logs table
$sql3 = "CREATE TABLE IF NOT EXISTS reminder_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reminder_id INT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    method ENUM('email', 'sms') NOT NULL,
    status ENUM('success', 'failed') NOT NULL,
    details TEXT,
    FOREIGN KEY (reminder_id) REFERENCES reminders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql3) === TRUE) {
    echo "<p style='color:green;'>✅ Table 'reminder_logs' created or already exists.</p>";
} else {
    echo "<p style='color:red;'>❌ Error creating 'reminder_logs': " . $conn->error . "</p>";
}

echo "<hr><p>Setup complete. You can now use the Medical Records module.</p>";
?>
