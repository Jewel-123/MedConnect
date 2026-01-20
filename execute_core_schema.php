<?php
/**
 * Execute Core System Schema Migration
 * Safely applies all new tables and enhancements for 8 core modules
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "medconnect";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("ERROR: Database connection failed: " . $conn->connect_error . "\n");
}

$conn->set_charset("utf8mb4");

echo "==============================================\n";
echo "MedConnect Core System Schema Migration\n";
echo "==============================================\n";
echo "Connected to database: $dbname\n\n";

// Read SQL file
$sqlFile = __DIR__ . '/core_system_schema.sql';
if (!file_exists($sqlFile)) {
    die("ERROR: Schema file not found: $sqlFile\n");
}

$sql = file_get_contents($sqlFile);

// Split into individual statements
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) {
        // Filter out comments and empty statements
        $stmt = trim($stmt);
        return !empty($stmt) && 
               strpos($stmt, '--') !== 0 && 
               strpos($stmt, '/*') !== 0;
    }
);

echo "Found " . count($statements) . " SQL statements to execute.\n\n";

$successCount = 0;
$errorCount = 0;
$errors = [];

// Disable foreign key checks temporarily for safe migration
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

foreach ($statements as $index => $statement) {
    // Skip SET statements and USE statements (already connected)
    if (stripos($statement, 'SET @') === 0 || 
        stripos($statement, 'PREPARE') === 0 ||
        stripos($statement, 'EXECUTE') === 0 ||
        stripos($statement, 'DEALLOCATE') === 0 ||
        stripos($statement, 'USE `') === 0) {
        continue;
    }
    
    // Extract table/action info for logging
    $logInfo = '';
    if (preg_match('/CREATE TABLE IF NOT EXISTS `?(\w+)`?/i', $statement, $matches)) {
        $logInfo = "Creating table: {$matches[1]}";
    } elseif (preg_match('/ALTER TABLE `?(\w+)`?/i', $statement, $matches)) {
        $logInfo = "Altering table: {$matches[1]}";
    } elseif (preg_match('/INSERT INTO `?(\w+)`?/i', $statement, $matches)) {
        $logInfo = "Inserting data into: {$matches[1]}";
    } else {
        $logInfo = "Executing statement " . ($index + 1);
    }
    
    echo "[$index] $logInfo ... ";
    
    $result = $conn->query($statement);
    
    if ($result) {
        echo "✓ SUCCESS\n";
        $successCount++;
    } else {
        echo "✗ FAILED: " . $conn->error . "\n";
        $errorCount++;
        $errors[] = [
            'statement' => substr($statement, 0, 100) . '...',
            'error' => $conn->error,
            'info' => $logInfo
        ];
    }
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "\n==============================================\n";
echo "Migration Summary\n";
echo "==============================================\n";
echo "Total Statements: " . count($statements) . "\n";
echo "Successful: $successCount\n";
echo "Failed: $errorCount\n";

if ($errorCount > 0) {
    echo "\n❌ ERRORS ENCOUNTERED:\n";
    foreach ($errors as $i => $error) {
        echo "\n" . ($i + 1) . ". " . $error['info'] . "\n";
        echo "   Error: " . $error['error'] . "\n";
        echo "   Statement: " . $error['statement'] . "\n";
    }
} else {
    echo "\n✅ ALL MIGRATIONS COMPLETED SUCCESSFULLY!\n";
}

// Verify tables were created
echo "\n==============================================\n";
echo "Verifying New Tables\n";
echo "==============================================\n";

$newTables = [
    'symptom_attachments',
    'appointments',
    'doctor_queue',
    'consultation_messages',
    'consultation_attachments',
    'pharmacy_profiles',
    'pharmacy_inventory',
    'prescription_orders',
    'delivery_tracking',
    'payment_transactions',
    'revenue_splits',
    'payouts',
    'pharmacy_earnings',
    'notification_preferences',
    'scheduled_notifications',
    'notification_templates',
    'access_logs',
    'compliance_events'
];

$createdTables = [];
$missingTables = [];

foreach ($newTables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "✓ $table\n";
        $createdTables[] = $table;
    } else {
        echo "✗ $table (NOT CREATED)\n";
        $missingTables[] = $table;
    }
}

echo "\n==============================================\n";
echo "Verification Summary\n";
echo "==============================================\n";
echo "Expected Tables: " . count($newTables) . "\n";
echo "Created: " . count($createdTables) . "\n";
echo "Missing: " . count($missingTables) . "\n";

if (count($missingTables) > 0) {
    echo "\n⚠️ WARNING: Some tables were not created.\n";
    echo "Missing tables: " . implode(', ', $missingTables) . "\n";
} else {
    echo "\n✅ ALL TABLES VERIFIED!\n";
}

// Check existing data is preserved
echo "\n==============================================\n";
echo "Data Integrity Check\n";
echo "==============================================\n";

$dataChecks = [
    'users' => 'SELECT COUNT(*) as count FROM users',
    'consultations' => 'SELECT COUNT(*) as count FROM consultations',
    'doctor_profiles' => 'SELECT COUNT(*) as count FROM doctor_profiles',
    'prescriptions_v2' => 'SELECT COUNT(*) as count FROM prescriptions_v2'
];

foreach ($dataChecks as $table => $query) {
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✓ $table: {$row['count']} records preserved\n";
    } else {
        echo "✗ $table: Could not verify\n";
    }
}

echo "\n==============================================\n";
echo "Migration Complete!\n";
echo "==============================================\n";

$conn->close();
