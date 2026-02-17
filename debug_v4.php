<?php
require_once 'db.php';

echo "--- Users Table Columns ---\n";
$res = $conn->query("SHOW COLUMNS FROM users");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "{$row['Field']} ({$row['Type']})\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\n--- Recent Consultations ---\n";
$res = $conn->query("SELECT * FROM consultations ORDER BY created_at DESC LIMIT 5");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
