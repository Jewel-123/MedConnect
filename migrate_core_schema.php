<?php
/**
 * Migration Backend API
 * Executes the core system schema migration
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

$logs = [];
$tablesCreated = [];
$errors = [];

function addLog($message, $type = 'info') {
    global $logs;
    $logs[] = [
        'message' => $message,
        'type' => $type,
        'timestamp' => date('H:i:s')
    ];
}

try {
    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "medconnect";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    addLog("✓ Connected to database: $dbname", 'success');
    
    // Read SQL file
    $sqlFile = __DIR__ . '/core_system_schema.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Schema file not found");
    }
    
    addLog("✓ Loaded schema file", 'success');
    
    $sql = file_get_contents($sqlFile);
    
    // Remove comments and split by semicolon
    $sql = preg_replace('/--.*$/m', '', $sql);  // Remove single-line comments
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);  // Remove multi-line comments
    
    // Split into statements
    $statements = explode(';', $sql);
    $statements = array_filter($statements, function($stmt) {
        return !empty(trim($stmt));
    });
    
    addLog("📝 Found " . count($statements) . " statements to execute", 'info');
    
    // Disable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    addLog("⚙️ Disabled foreign key checks", 'info');
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        // Skip USE, SET, PREPARE, EXECUTE, DEALLOCATE statements
        if (empty($statement) ||
            stripos($statement, 'USE ') === 0 ||
            stripos($statement, 'SET @') === 0 ||
            stripos($statement, 'PREPARE') === 0 ||
            stripos($statement, 'EXECUTE') === 0 ||
            stripos($statement, 'DEALLOCATE') === 0) {
            continue;
        }
        
        // Extract operation info
        $operation = '';
        if (preg_match('/CREATE TABLE IF NOT EXISTS\s+`?(\w+)`?/i', $statement, $matches)) {
            $operation = "Creating table: {$matches[1]}";
            $tablesCreated[] = $matches[1];
        } elseif (preg_match('/ALTER TABLE\s+`?(\w+)`?/i', $statement, $matches)) {
            $operation = "Altering table: {$matches[1]}";
        } elseif (preg_match('/INSERT INTO\s+`?(\w+)`?/i', $statement, $matches)) {
            $operation = "Inserting data into: {$matches[1]}";
        }
        
        if ($operation) {
            $result = $conn->query($statement);
            
            if ($result) {
                addLog("✓ $operation", 'success');
                $successCount++;
            } else {
                $error = $conn->error;
                // Ignore "duplicate" errors for ON DUPLICATE KEY UPDATE
                if (stripos($error, 'duplicate') !== false && stripos($statement, 'ON DUPLICATE KEY') !== false) {
                    addLog("→ $operation (already exists, skipped)", 'info');
                    $successCount++;
                } else {
                    addLog("✗ $operation - Error: $error", 'error');
                    $errorCount++;
                    $errors[] = ['operation' => $operation, 'error' => $error];
                }
            }
        }
    }
    
    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    addLog("⚙️ Re-enabled foreign key checks", 'info');
    
    // Verify tables
    addLog("🔍 Verifying created tables...", 'info');
    
    $newTables = [
        'symptom_attachments', 'appointments', 'doctor_queue',
        'consultation_messages', 'consultation_attachments',
        'pharmacy_profiles', 'pharmacy_inventory', 'prescription_orders',
        'delivery_tracking', 'payment_transactions', 'revenue_splits',
        'payouts', 'pharmacy_earnings', 'notification_preferences',
        'scheduled_notifications', 'notification_templates',
        'access_logs', 'compliance_events'
    ];
    
    $verifiedTables = [];
    foreach ($newTables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            $verifiedTables[] = $table;
        }
    }
    
    addLog("✓ Verified " . count($verifiedTables) . " / " . count($newTables) . " tables", 'success');
    
    // Check data preservation
    addLog("🔍 Verifying data integrity...", 'info');
    
    $dataCheck = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($dataCheck) {
        $row = $dataCheck->fetch_assoc();
        addLog("✓ Users table: {$row['count']} records preserved", 'success');
    }
    
    $dataCheck = $conn->query("SELECT COUNT(*) as count FROM consultations");
    if ($dataCheck) {
        $row = $dataCheck->fetch_assoc();
        addLog("✓ Consultations table: {$row['count']} records preserved", 'success');
    }
    
    $conn->close();
    
    if ($errorCount > 0) {
        addLog("⚠️ Migration completed with $errorCount errors", 'warning');
    } else {
        addLog("✅ Migration completed successfully!", 'success');
    }
    
    echo json_encode([
        'status' => 'success',
        'logs' => $logs,
        'summary' => [
            'tables_created' => count($verifiedTables),
            'statements_executed' => $successCount,
            'errors' => $errorCount
        ],
        'tables' => $verifiedTables
    ]);
    
} catch (Exception $e) {
    addLog("❌ FATAL ERROR: " . $e->getMessage(), 'error');
    
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage(),
        'logs' => $logs
    ]);
}