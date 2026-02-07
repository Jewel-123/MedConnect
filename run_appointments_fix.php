<?php
/**
 * Run the appointments table fix
 */
require_once 'db.php';

echo "<!DOCTYPE html><html><head><title>Fix Appointments Table</title></head><body>";
echo "<h2>Fixing Appointments Table Schema...</h2>";

// Read and execute the SQL file
$sql = file_get_contents('fix_appointments_table.sql');

// Split by semicolons and execute each statement
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (empty($statement) || strpos($statement, '--') === 0) continue;
    
    try {
        $conn->query($statement);
        echo "<p style='color: green;'>✓ Executed successfully</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    }
}

echo "<h3>Checking table structure...</h3>";
$result = $conn->query("DESCRIBE appointments");
echo "<table border='1' style='border-collapse:collapse;'><tr><th>Column</th><th>Type</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
}
echo "</table>";

echo "<p><strong>Done! Now try booking an appointment again.</strong></p>";
echo "</body></html>";