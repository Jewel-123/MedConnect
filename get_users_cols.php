<?php
require_once 'db.php';
$res = $conn->query("DESCRIBE users");
$cols = [];
while($row = $res->fetch_assoc()) {
    $cols[] = $row['Field'];
}
file_put_contents('users_columns_list.txt', implode("\n", $cols));
echo "Users columns listed successfully in users_columns_list.txt\n";