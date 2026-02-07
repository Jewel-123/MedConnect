<?php
// Report all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "--- Connection Test ---\n";
include 'db.php';
if (isset($conn)) {
    echo "Connection object exists.\n";
    if ($conn->connect_error) {
        echo "Connection Error: " . $conn->connect_error . "\n";
    } else {
        echo "Connection Successful.\n";
        echo "Current Database: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "\n";
    }
} else {
    echo "Connection object NOT found. Check db.php\n";
}

echo "\n--- Databases ---\n";
$res = $conn->query("SHOW DATABASES");
while($row = $res->fetch_array()) {
    echo $row[0] . "\n";
}

echo "\n--- Tables in medconnect ---\n";
$conn->select_db("medconnect");
$res = $conn->query("SHOW TABLES");
if ($res) {
    while($row = $res->fetch_array()) {
        echo $row[0] . "\n";
    }
} else {
    echo "Could not query tables. Maybe database 'medconnect' missing.\n";
}

if ($res && $res->num_rows > 0) {
    echo "\n--- Schema of users ---\n";
    $res = $conn->query("DESCRIBE users");
    if ($res) {
        while($row = $res->fetch_assoc()) {
            echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']} - {$row['Default']}\n";
        }
    } else {
        echo "Users table missing or error.\n";
    }
}