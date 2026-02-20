<?php
require_once 'db.php';

echo "--- Schema of 'symptom_checks' ---\n";
$res = $conn->query("DESC symptom_checks");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "{$row['Field']} | {$row['Type']}\n";
    }
} else {
    echo "Table 'symptom_checks' not found or error: " . $conn->error . "\n";
}

echo "\n--- Data for Patient 21 (Jewel Biju) in 'symptom_checks' ---\n";
$res = $conn->query("SELECT * FROM symptom_checks WHERE user_id = 21 OR patient_id = 21 ORDER BY id DESC LIMIT 5");
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "No records found for Patient 21.\n";
}
?>
