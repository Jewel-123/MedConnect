<?php
/**
 * Execute Consultation Management Schema Enhancements
 */

require_once 'db.php';

echo "==================================\n";
echo "Consultation Management Schema Enhancement\n";
echo "==================================\n\n";

// Read SQL file
$sql = file_get_contents('consultation_management_schema_enhancement.sql');

// Execute multi-query
if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            while ($row = $result->fetch_assoc()) {
                foreach ($row as $key => $value) {
                    echo "$key: $value\n";
                }
                echo "\n";
            }
            $result->free();
        }
    } while ($conn->next_result());
    
    echo "\n✓ Schema enhancements executed successfully!\n";
} else {
    echo "Error executing schema: " . $conn->error . "\n";
    exit(1);
}

$conn->close();