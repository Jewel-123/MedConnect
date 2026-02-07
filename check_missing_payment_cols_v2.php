<?php
require_once 'db.php';
foreach(['appointments','prescription_orders'] as $t){ 
    echo "--- $t ---\n"; 
    $res=$conn->query("DESCRIBE $t"); 
    if ($res) {
        while($r=$res->fetch_assoc()){ 
            echo $r['Field']."\n"; 
        } 
    } else {
        echo "Error: " . $conn->error . "\n";
    }
}