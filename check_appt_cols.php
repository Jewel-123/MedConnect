<?php
require_once 'db.php';
$cols = $conn->query("SHOW COLUMNS FROM appointments");
$output = "";
while ($row = $cols->fetch_assoc()) {
    $output .= $row['Field'] . " (" . $row['Type'] . ")\n";
}
file_put_contents('appt_cols.txt', $output);
echo "Columns dumped to appt_cols.txt";
?>
