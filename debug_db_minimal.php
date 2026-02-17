<?php
$conn = new mysqli("localhost", "root", "", "medconnect");
if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error;
    exit;
}
echo "Connected successfully\n";

$res = $conn->query("SELECT 1");
if ($res) {
    echo "Query 1 success\n";
    $res->free();
} else {
    echo "Query 1 failed: " . $conn->error . "\n";
}

$res = $conn->query("DESCRIBE appointments");
if ($res) {
    echo "Query 2 success (DESCRIBE appointments)\n";
    while($row = $res->fetch_assoc()) {
        echo "Field: {$row['Field']}\n";
    }
    $res->free();
} else {
    echo "Query 2 failed: " . $conn->error . "\n";
}

$conn->close();
echo "Done\n";
?>
