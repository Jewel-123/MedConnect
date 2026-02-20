<?php
require_once 'db.php';

function dumpTable($conn, $table) {
    echo "--- Table: $table ---\n";
    $res = $conn->query("DESC $table");
    while ($row = $res->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
    echo "\n";
}

dumpTable($conn, 'appointments');
dumpTable($conn, 'consultations');
?>
