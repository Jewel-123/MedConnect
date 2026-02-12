<?php
/**
 * Pharmacy Billing System - Database Schema Setup
 * Creates and updates all necessary tables for medicine pricing and billing
 */

require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Pharmacy Billing System - Schema Setup</h1>";
echo "<pre>";

$errors = [];
$success = [];

try {
    // ================================================================
    // 1. CREATE MEDICINES TABLE
    // ================================================================
    echo "\n=== Creating Medicines Table ===\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS `medicines` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL UNIQUE,
        `generic_name` VARCHAR(255) NULL,
        `category` VARCHAR(100) NULL,
        `manufacturer` VARCHAR(255) NULL,
        `price` DECIMAL(10,2) NOT NULL,
        `stock` INT NOT NULL DEFAULT 0,
        `low_stock_threshold` INT DEFAULT 10,
        `unit` VARCHAR(50) DEFAULT 'tablets',
        `requires_prescription` BOOLEAN DEFAULT TRUE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_name` (`name`),
        INDEX `idx_category` (`category`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    
    if ($conn->query($sql)) {
        $success[] = "✓ Medicines table created/verified";
        echo "✓ Medicines table created/verified\n";
    } else {
        throw new Exception("Failed to create medicines table: " . $conn->error);
    }

    // ================================================================
    // 2. ADD MEDICINE_ID TO PRESCRIPTION_ITEMS_V2
    // ================================================================
    echo "\n=== Adding medicine_id to prescription_items_v2 ===\n";
    
    // Check if column exists
    $check = $conn->query("SHOW COLUMNS FROM prescription_items_v2 LIKE 'medicine_id'");
    
    if ($check->num_rows == 0) {
        $sql = "ALTER TABLE `prescription_items_v2` 
                ADD COLUMN `medicine_id` INT NULL AFTER `prescription_id`,
                ADD INDEX `idx_medicine_id` (`medicine_id`)";
        
        if ($conn->query($sql)) {
            $success[] = "✓ Added medicine_id column to prescription_items_v2";
            echo "✓ Added medicine_id column to prescription_items_v2\n";
        } else {
            throw new Exception("Failed to add medicine_id: " . $conn->error);
        }
    } else {
        echo "✓ medicine_id column already exists\n";
        $success[] = "✓ medicine_id column already exists";
    }

    // ================================================================
    // 3. UPDATE PRESCRIPTIONS_V2 TABLE
    // ================================================================
    echo "\n=== Updating prescriptions_v2 table ===\n";
    
    $columns_to_add = [
        'total_amount' => "ADD COLUMN `total_amount` DECIMAL(10,2) DEFAULT 0.00",
        'ordered_at' => "ADD COLUMN `ordered_at` TIMESTAMP NULL",
        'verified_at' => "ADD COLUMN `verified_at` TIMESTAMP NULL",
        'bill_generated_at' => "ADD COLUMN `bill_generated_at` TIMESTAMP NULL",
        'dispensed_at' => "ADD COLUMN `dispensed_at` TIMESTAMP NULL",
        'pharmacist_id' => "ADD COLUMN `pharmacist_id` INT NULL"
    ];
    
    foreach ($columns_to_add as $col => $alter) {
        $check = $conn->query("SHOW COLUMNS FROM prescriptions_v2 LIKE '$col'");
        if ($check->num_rows == 0) {
            if ($conn->query("ALTER TABLE prescriptions_v2 $alter")) {
                echo "✓ Added column: $col\n";
            } else {
                echo "✗ Failed to add $col: " . $conn->error . "\n";
            }
        } else {
            echo "✓ Column $col already exists\n";
        }
    }
    
    // Update status enum to include new workflow statuses
    echo "\n=== Updating prescription status enum ===\n";
    $sql = "ALTER TABLE `prescriptions_v2` 
            MODIFY COLUMN `status` ENUM(
                'Created', 'Pending', 'Verified', 'Awaiting Payment', 
                'Paid', 'Dispensed', 'Completed', 'Cancelled',
                'draft', 'finalized', 'sent_to_pharmacy', 'in_progress', 'ready', 'completed', 'cancelled'
            ) DEFAULT 'Created'";
    
    if ($conn->query($sql)) {
        echo "✓ Updated prescription status enum\n";
        $success[] = "✓ Updated prescription status enum";
    } else {
        echo "Note: Status enum update skipped (may already be updated)\n";
    }

    // ================================================================
    // 4. CREATE/VERIFY PAYMENTS TABLE
    // ================================================================
    echo "\n=== Creating Payments Table ===\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS `payments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `prescription_id` INT NOT NULL,
        `patient_id` INT NOT NULL,
        `amount` DECIMAL(10,2) NOT NULL,
        `status` ENUM('Pending', 'Paid', 'Failed', 'Refunded') DEFAULT 'Pending',
        `payment_method` VARCHAR(50) NULL,
        `transaction_id` VARCHAR(255) NULL,
        `razorpay_order_id` VARCHAR(255) NULL,
        `razorpay_payment_id` VARCHAR(255) NULL,
        `paid_at` TIMESTAMP NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_prescription` (`prescription_id`),
        INDEX `idx_patient` (`patient_id`),
        INDEX `idx_status` (`status`)
    )";
    
    if ($conn->query($sql)) {
        $success[] = "✓ Payments table created/verified";
        echo "✓ Payments table created/verified\n";
    } else {
        throw new Exception("Failed to create payments table: " . $conn->error);
    }

    // ================================================================
    // 5. ADD INDEXES FOR PERFORMANCE
    // ================================================================
    echo "\n=== Adding Performance Indexes ===\n";
    
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_presc_status ON prescriptions_v2(status)",
        "CREATE INDEX IF NOT EXISTS idx_presc_pharmacy ON prescriptions_v2(pharmacy_id, status)",
        "CREATE INDEX IF NOT EXISTS idx_presc_patient ON prescriptions_v2(patient_id, status)"
    ];
    
    foreach ($indexes as $idx_sql) {
        if ($conn->query($idx_sql)) {
            echo "✓ Index created\n";
        }
    }

    // ================================================================
    // 6. MIGRATE EXISTING DATA
    // ================================================================
    echo "\n=== Migrating Existing Data ===\n";
    
    // Update old status values to new ones
    $migrations = [
        "UPDATE prescriptions_v2 SET status = 'Created' WHERE status = 'draft'",
        "UPDATE prescriptions_v2 SET status = 'Pending' WHERE status = 'sent_to_pharmacy'",
        "UPDATE prescriptions_v2 SET status = 'Completed' WHERE status IN ('finalized', 'ready', 'completed')"
    ];
    
    foreach ($migrations as $migrate) {
        $conn->query($migrate);
    }
    echo "✓ Migrated existing prescription statuses\n";

    // ================================================================
    // SUMMARY
    // ================================================================
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "SETUP COMPLETE!\n";
    echo str_repeat("=", 60) . "\n\n";
    
    echo "Success Summary:\n";
    foreach ($success as $msg) {
        echo "$msg\n";
    }
    
    if (!empty($errors)) {
        echo "\nErrors:\n";
        foreach ($errors as $err) {
            echo "✗ $err\n";
        }
    }
    
    echo "\nNext Steps:\n";
    echo "1. Run: c:\\xampp\\php\\php.exe seed_medicines_pharmaceutical.php\n";
    echo "2. Link medicines to prescription items\n";
    echo "3. Test the billing workflow\n";

} catch (Exception $e) {
    echo "\n✗ CRITICAL ERROR: " . $e->getMessage() . "\n";
    $errors[] = $e->getMessage();
}

echo "</pre>";
?>
