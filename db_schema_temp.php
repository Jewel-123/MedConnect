<?php
require_once 'db.php';
$tables = ['prescriptions_v2', 'consultations', 'users', 'prescription_items_v2', 'appointments'];
$out = "";
foreach ($tables as $table) {
    $out .= "--- Table: $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $out .= "{$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Key']} | {$row['Default']}\n";
        }
    } else {
        $out .= "Error: " . $conn->error . "\n";
    }
    $out .= "\n";
}
file_put_contents('schema_check_output.txt', $out);
echo "Schema written to schema_check_output.txt";
?>
