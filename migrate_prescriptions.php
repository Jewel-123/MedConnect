<?php
include 'db.php';

$queries = [
    // Ensure all requested columns exist in prescriptions_v2
    "ALTER TABLE prescriptions_v2 ADD COLUMN IF NOT EXISTS total_amount DECIMAL(10,2) DEFAULT 0.00",
    "ALTER TABLE prescriptions_v2 ADD COLUMN IF NOT EXISTS payment_status ENUM('Unpaid', 'Paid') DEFAULT 'Unpaid'",
    "ALTER TABLE prescriptions_v2 ADD COLUMN IF NOT EXISTS payment_id VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE prescriptions_v2 ADD COLUMN IF NOT EXISTS ordered_at TIMESTAMP NULL DEFAULT NULL",
    "ALTER TABLE prescriptions_v2 ADD COLUMN IF NOT EXISTS verified_at TIMESTAMP NULL DEFAULT NULL",
    "ALTER TABLE prescriptions_v2 ADD COLUMN IF NOT EXISTS paid_at TIMESTAMP NULL DEFAULT NULL",
    "ALTER TABLE prescriptions_v2 ADD COLUMN IF NOT EXISTS dispensed_at TIMESTAMP NULL DEFAULT NULL",
    "ALTER TABLE prescriptions_v2 ADD COLUMN IF NOT EXISTS completed_at TIMESTAMP NULL DEFAULT NULL",
    "ALTER TABLE prescriptions_v2 ADD COLUMN IF NOT EXISTS pharmacist_id INT DEFAULT NULL",
    
    // Update status column to include new statuses if it's an ENUM or just ensure it's a VARCHAR
    "ALTER TABLE prescriptions_v2 MODIFY COLUMN status VARCHAR(50) DEFAULT 'issued'",
    
    // Ensure pharmacy_id foreign key or at least the column exists
    "ALTER TABLE prescriptions_v2 ADD COLUMN IF NOT EXISTS pharmacy_id INT DEFAULT NULL"
];

foreach ($queries as $query) {
    if ($conn->query($query)) {
        echo "Successfully executed: $query\n";
    } else {
        echo "Error executing query: " . $conn->error . "\n";
    }
}
?>
