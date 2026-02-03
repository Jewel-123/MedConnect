<?php
/**
 * Execute Medical AI Schema
 * Runs the schema SQL file to create tables
 */

require_once 'db.php';

echo "========================================\n";
echo "Creating Medical AI Database Schema\n";
echo "========================================\n\n";

// Read the SQL file
$sqlFile = __DIR__ . '/medical_ai_schema.sql';
$sql = file_get_contents($sqlFile);

// Remove comments and split by semicolons
$sql = preg_replace('/--.*$/m', '', $sql);
$sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
$statements = array_filter(array_map('trim', explode(';', $sql)));

$successCount = 0;
$errorCount = 0;

foreach ($statements as $statement) {
    if (empty($statement)) continue;
    
    // Skip USE statements (we're already connected)
    if (stripos($statement, 'USE ') === 0) continue;
    
    // Skip SELECT statements (just messages)
    if (stripos($statement, 'SELECT ') === 0) {
        echo "✓ Schema execution in progress...\n";
        continue;
    }
    
    if ($conn->query($statement)) {
        $successCount++;
        // Extract table name if CREATE TABLE statement
        if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
            echo "✓ Created table: {$matches[1]}\n";
        }
    } else {
        $errorCount++;
        echo "✗ Error: " . $conn->error . "\n";
    }
}

echo "\n========================================\n";
echo "Schema Execution Complete\n";
echo "========================================\n";
echo "Successful statements: $successCount\n";
echo "Errors: $errorCount\n";
echo "========================================\n";

$conn->close();
?>
