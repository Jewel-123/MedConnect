<?php
require_once 'db.php';
if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error;
} else {
    echo "Connection successful!";
    $res = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($res) {
        $row = $res->fetch_assoc();
        echo " Total users: " . $row['count'];
    } else {
        echo " Error querying users table: " . $conn->error;
    }
}
?>
