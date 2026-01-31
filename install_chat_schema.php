<?php
/**
 * Execute Consultation Chat Schema - Web Version
 * Runs the database migration for enhanced chat features
 */

require_once 'db.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Chat Schema Installation</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .skip { color: #ce9178; }
        .header { color: #569cd6; font-weight: bold; }
    </style>
</head>
<body>
<pre>
<?php

echo "<span class='header'>========================================\n";
echo "MedConnect - Chat Schema Installation\n";
echo "========================================\n\n</span>";

// Read the SQL file
$sqlFile = __DIR__ . '/consultation_chat_schema.sql';

if (!file_exists($sqlFile)) {
    die("<span class='error'>ERROR: Schema file not found: $sqlFile</span>\n");
}

$sql = file_get_contents($sqlFile);

// Remove USE statement
$sql = preg_replace('/USE\s+`medconnect`;/i', '', $sql);

// Split by semicolons
$statements = explode(';', $sql);

$successCount = 0;
$errorCount = 0;
$skippedCount = 0;

foreach ($statements as $statement) {
    $statement = trim($statement);
    
    // Skip empty statements and comments
    if (empty($statement) || strpos($statement, '--') === 0 || strpos($statement, '/*') === 0) {
        continue;
    }
    
    try {
        if ($conn->query($statement) === TRUE) {
            $successCount++;
            
            // Extract table name for better logging
            if (preg_match('/CREATE TABLE.*?`(\w+)`/i', $statement, $matches)) {
                echo "<span class='success'>✓ Created table: {$matches[1]}</span>\n";
            } elseif (preg_match('/INSERT.*?INTO.*?`(\w+)`/i', $statement, $matches)) {
                $affected = $conn->affected_rows;
                echo "<span class='success'>✓ Inserted $affected row(s) into: {$matches[1]}</span>\n";
            } else {
                echo "<span class='success'>✓ Executed statement</span>\n";
            }
        }
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        
        // Check if error is just "already exists"
        if (strpos($errorMsg, 'already exists') !== false || 
            strpos($errorMsg, 'Duplicate') !== false) {
            $skippedCount++;
            echo "<span class='skip'>⊘ Skipped (already exists)</span>\n";
        } else {
            $errorCount++;
            echo "<span class='error'>✗ ERROR: $errorMsg</span>\n";
        }
    }
}

echo "\n<span class='header'>========================================\n";
echo "SUMMARY\n";
echo "========================================</span>\n";
echo "Successful: <span class='success'>$successCount</span>\n";
echo "Errors: <span class='error'>$errorCount</span>\n";
echo "Skipped: <span class='skip'>$skippedCount</span>\n";

// Verification queries
echo "\n<span class='header'>========================================\n";
echo "VERIFICATION\n";
echo "========================================</span>\n\n";

// Check messages table
$result = $conn->query("DESCRIBE messages");
if ($result) {
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    echo "<span class='success'>✓ Messages table columns:</span>\n";
    echo "  " . implode(', ', $columns) . "\n\n";
}

// Check clinical notes table
$result = $conn->query("SHOW TABLES LIKE 'consultation_clinical_notes'");
if ($result && $result->num_rows > 0) {
    echo "<span class='success'>✓ consultation_clinical_notes table exists</span>\n";
    
    $countResult = $conn->query("SELECT COUNT(*) as count FROM consultation_clinical_notes");
    if ($countResult && $row = $countResult->fetch_assoc()) {
        echo "  Records: {$row['count']}\n\n";
    }
} else {
    echo "<span class='error'>✗ consultation_clinical_notes table NOT found</span>\n\n";
}

// Check workflow templates
$result = $conn->query("SHOW TABLES LIKE 'workflow_guidance_templates'");
if ($result && $result->num_rows > 0) {
    echo "<span class='success'>✓ workflow_guidance_templates table exists</span>\n";
    
    $countResult = $conn->query("SELECT COUNT(*) as count FROM workflow_guidance_templates");
    if ($countResult && $row = $countResult->fetch_assoc()) {
        echo "  Templates loaded: {$row['count']}\n\n";
    }
} else {
    echo "<span class='error'>✗ workflow_guidance_templates table NOT found</span>\n\n";
}

// Check message classification log
$result = $conn->query("SHOW TABLES LIKE 'message_classification_log'");
if ($result && $result->num_rows > 0) {
    echo "<span class='success'>✓ message_classification_log table exists</span>\n\n";
} else {
    echo "<span class='error'>✗ message_classification_log table NOT found</span>\n\n";
}

echo "<span class='header'>========================================\n";
echo "Schema installation complete!\n";
echo "========================================</span>\n";
?>
</pre>
</body>
</html>
