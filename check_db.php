<?php
include 'db.php';
$table = 'doctor_reviews';
$res = $conn->query("DESC $table");
echo "Table: $table\n";
while ($row = $res->fetch_assoc()) {
    echo "Field: " . $row['Field'] . "\n";
}
?>
