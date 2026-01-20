<?php
require_once 'db.php';

header('Content-Type: application/json');

// Get the actual column names from appointments table
$result = $conn->query("DESCRIBE appointments");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

echo json_encode([
    'columns' => $columns,
    'message' => 'These are the actual columns in your appointments table'
]);
?>
