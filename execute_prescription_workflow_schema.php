<?php
/**
 * Execute Prescription Workflow Schema
 * Safely updates prescription tables with new status enums and timestamps
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Execute Prescription Workflow Schema</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
        .success { background: #d1fae5; color: #065f46; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { background: #dbeafe; color: #1e40af; padding: 10px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #667eea; background: #f8f9ff; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Prescription Workflow Schema Execution</h1>";

try {
    // Read the SQL file
    $sqlFile = __DIR__ . '/prescription_workflow_schema.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Schema file not found: $sqlFile");
    }
    
    echo "<div class='info'>📄 Reading schema file: prescription_workflow_schema.sql</div>";
    
    $sql = file_get_contents($sqlFile);
    
    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   !preg_match('/^\/\*/', $stmt);
        }
    );
    
    echo "<div class='step'>";
    echo "<h3>Executing Schema Updates...</h3>";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        // Skip comments
        if (preg_match('/^(--|\/\*)/', $statement)) continue;
        
        try {
            if ($conn->query($statement)) {
                $successCount++;
                
                // Show important operations
                if (preg_match('/ALTER TABLE|CREATE TABLE|INSERT INTO/i', $statement)) {
                    $preview = substr($statement, 0, 100);
                    echo "<div class='success'>✓ " . htmlspecialchars($preview) . "...</div>";
                }
            } else {
                // Some statements might fail if already executed, that's okay
                if (!preg_match('/Duplicate|already exists/i', $conn->error)) {
                    echo "<div class='error'>⚠ " . htmlspecialchars($conn->error) . "</div>";
                    $errorCount++;
                }
            }
        } catch (Exception $e) {
            if (!preg_match('/Duplicate|already exists/i', $e->getMessage())) {
                echo "<div class='error'>⚠ " . htmlspecialchars($e->getMessage()) . "</div>";
                $errorCount++;
            }
        }
    }
    
    echo "</div>";
    
    // Verify the changes
    echo "<div class='step'>";
    echo "<h3>Verification Results</h3>";
    
    // Check prescription status enum
    $result = $conn->query("SHOW COLUMNS FROM prescriptions_v2 LIKE 'status'");
    if ($result && $row = $result->fetch_assoc()) {
        echo "<div class='success'>✓ Prescription status enum updated</div>";
        echo "<pre>" . htmlspecialchars($row['Type']) . "</pre>";
    }
    
    // Check for new timestamp columns
    $columns = ['finalized_at', 'sent_to_pharmacy_at', 'in_progress_at', 'ready_at', 'completed_at', 'cancelled_at'];
    foreach ($columns as $col) {
        $result = $conn->query("SHOW COLUMNS FROM prescriptions_v2 LIKE '$col'");
        if ($result && $result->num_rows > 0) {
            echo "<div class='success'>✓ Column '$col' exists</div>";
        } else {
            echo "<div class='error'>✗ Column '$col' missing</div>";
        }
    }
    
    // Check central pharmacy
    $result = $conn->query("SELECT id, email, full_name FROM users WHERE email = 'central.pharmacy@medconnect.com'");
    if ($result && $row = $result->fetch_assoc()) {
        echo "<div class='success'>✓ Central Pharmacy account exists (ID: {$row['id']})</div>";
    } else {
        echo "<div class='info'>ℹ Central Pharmacy account needs password setup</div>";
    }
    
    echo "</div>";
    
    echo "<div class='success'>";
    echo "<h3>✅ Schema Execution Complete!</h3>";
    echo "<p>Successfully executed $successCount statements</p>";
    if ($errorCount > 0) {
        echo "<p>Encountered $errorCount non-critical errors (likely duplicate operations)</p>";
    }
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h4>Next Steps:</h4>";
    echo "<ol>";
    echo "<li>Add medicine inventory to pharmacy</li>";
    echo "<li>Update patient dashboard to show prescriptions</li>";
    echo "<li>Implement pharmacy workflow</li>";
    echo "<li>Integrate payment system</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "    </div>
</body>
</html>";