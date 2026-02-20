<?php
require_once 'db.php';

$res = $conn->query("SELECT COUNT(*) FROM symptom_checks");
if ($res) {
    echo "Count: " . $res->fetch_row()[0] . "\n";
    
    $res = $conn->query("SELECT * FROM symptom_checks ORDER BY id DESC LIMIT 5");
    while ($row = $res->fetch_assoc()) {
        echo json_encode($row) . "\n";
    }
} else {
    echo "Query failed: " . $conn->error . "\n";
}
?>
