<?php
require_once 'db.php';
$res=$conn->query("SELECT id, full_name, role FROM users WHERE id BETWEEN 3 AND 13");
if ($res->num_rows > 0) {
    echo "Found potentially stale users:\n";
    while($row=$res->fetch_assoc()){
        echo $row['id'].': '.$row['full_name'].' ('.$row['role'].')'."\n";
    }
} else {
    echo "No stale users found in range 3-13.\n";
}