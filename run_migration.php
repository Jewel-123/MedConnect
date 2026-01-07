<?php
/**
 * Migration Script - Create Consultations Table
 * Run this file once to create the consultations table
 */

require_once 'db.php';

header('Content-Type: application/json');

try {
    // Read the SQL migration file
    $sqlFile = __DIR__ . '/create_consultations_table.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Execute the SQL
    $conn->multi_query($sql);
    
    // Clear any remaining results
    while ($conn->next_result()) {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    }
    
    // Verify table was created
    $checkTable = $conn->query("SHOW TABLES LIKE 'consultations'");
    
    if ($checkTable && $checkTable->num_rows > 0) {
        // Get table structure
        $structure = $conn->query("DESCRIBE consultations");
        $columns = [];
        
        while ($row = $structure->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Consultations table created successfully!',
            'columns' => $columns
        ], JSON_PRETTY_PRINT);
    } else {
        throw new Exception("Table creation verification failed");
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

$conn->close();
?>
