<?php
/**
 * Add Enhanced Columns to Messages Table
 * Adds message classification and workflow tracking columns
 */

require_once 'db.php';

header('Content-Type: text/plain');

echo "Adding enhanced columns to messages table...\n\n";

$columns = [
    'message_classification' => "ENUM('non_clinical', 'partial_symptom', 'detailed_symptom', 'follow_up', 'general') DEFAULT 'general'",
    'workflow_stage' => "VARCHAR(50) DEFAULT NULL",
    'requires_response' => "BOOLEAN DEFAULT TRUE",
    'ai_suggestion' => "TEXT DEFAULT NULL"
];

foreach ($columns as $columnName => $columnDef) {
    // Check if column exists
    $result = $conn->query("SHOW COLUMNS FROM messages LIKE '$columnName'");
    
    if ($result->num_rows == 0) {
        // Column doesn't exist, add it
        $sql = "ALTER TABLE messages ADD COLUMN $columnName $columnDef";
        if ($conn->query($sql)) {
            echo "✓ Added column: $columnName\n";
        } else {
            echo "✗ Error adding $columnName: " . $conn->error . "\n";
        }
    } else {
        echo "⊘ Column already exists: $columnName\n";
    }
}

// Add indexes
echo "\nAdding indexes...\n";

$indexes = [
    'idx_classification' => 'message_classification',
    'idx_workflow_stage' => 'workflow_stage'
];

foreach ($indexes as $indexName => $columnName) {
    // Check if index exists
    $result = $conn->query("SHOW INDEX FROM messages WHERE Key_name = '$indexName'");
    
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE messages ADD INDEX $indexName ($columnName)";
        if ($conn->query($sql)) {
            echo "✓ Added index: $indexName\n";
        } else {
            echo "✗ Error adding index $indexName: " . $conn->error . "\n";
        }
    } else {
        echo "⊘ Index already exists: $indexName\n";
    }
}

echo "\n✓ Messages table enhancement complete!\n";