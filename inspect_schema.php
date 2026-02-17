<?php
require_once 'db.php';

$output = "";
function describe($conn, $table) {
    global $output;
    $output .= "=== Table: $table ===\n";
    $res = $conn->query("DESCRIBE $table");
    while($row = $res->fetch_assoc()) {
        $output .= "{$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Default']}\n";
    }
    $output .= "\n";
}

describe($conn, 'appointments');
describe($conn, 'consultations');
describe($conn, 'doctor_profiles');

file_put_contents('schema_info.txt', $output);
?>
