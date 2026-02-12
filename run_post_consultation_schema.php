&lt;?php
/**
 * Execute Post-Consultation Schema Updates
 * Run this file once to add required database columns
 */

require_once 'db.php';

echo "&lt;h2&gt;Post-Consultation Schema Update&lt;/h2&gt;";
echo "&lt;pre&gt;";

// Read SQL file
$sql = file_get_contents('update_post_consultation_schema.sql');

if (!$sql) {
    die("Error: Could not read SQL file\n");
}

// Split into individual statements
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) {
        return !empty($stmt) && 
               !preg_match('/^--/', $stmt) && 
               !preg_match('/^SELECT.*already exists/', $stmt);
    }
);

$success = 0;
$errors = 0;

foreach ($statements as $statement) {
    // Skip comments and empty statements
    if (empty(trim($statement)) || strpos(trim($statement), '--') === 0) {
        continue;
    }
    
    try {
        if ($conn->query($statement)) {
            $success++;
            echo "✓ Executed successfully\n";
        } else {
            $errors++;
            echo "✗ Error: " . $conn->error . "\n";
        }
    } catch (Exception $e) {
        $errors++;
        echo "✗ Exception: " . $e->getMessage() . "\n";
    }
}

echo "\n========================================\n";
echo "Schema Update Summary:\n";
echo "  Successful: $success\n";
echo "  Errors: $errors\n";
echo "========================================\n";

// Verify columns exist
echo "\nVerifying columns...\n";
$verify = $conn->query("
    SELECT 
        TABLE_NAME,
        COLUMN_NAME,
        DATA_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() 
        AND (
            (TABLE_NAME = 'consultations' AND COLUMN_NAME IN ('diagnosis', 'medical_advice', 'follow_up_scheduled', 'follow_up_notes'))
            OR
            (TABLE_NAME = 'prescriptions_v2' AND COLUMN_NAME = 'auto_sent_to_pharmacy')
        )
    ORDER BY TABLE_NAME, ORDINAL_POSITION
");

if ($verify) {
    echo "\nColumns added:\n";
    while ($row = $verify->fetch_assoc()) {
        echo "  • {$row['TABLE_NAME']}.{$row['COLUMN_NAME']} ({$row['DATA_TYPE']})\n";
    }
} else {
    echo "Error verifying columns: " . $conn->error . "\n";
}

echo "\n✓ Schema update completed!\n";
echo "&lt;/pre&gt;";

$conn->close();
?&gt;
