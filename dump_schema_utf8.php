<?php
require_once 'db.php';

$output = "--- Consultations Schema ---\n";
$res = $conn->query("DESC consultations");
while ($row = $res->fetch_assoc()) {
    $output .= "{$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Key']} | {$row['Default']} | {$row['Extra']}\n";
}

$output .= "\n--- Appointments Schema ---\n";
$res = $conn->query("DESC appointments");
while ($row = $res->fetch_assoc()) {
    $output .= "{$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Key']} | {$row['Default']} | {$row['Extra']}\n";
}

file_put_contents('full_schema_utf8.txt', $output);
echo "Schema dumped to full_schema_utf8.txt\n";
?>
