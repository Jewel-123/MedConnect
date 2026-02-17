<?php
require_once 'db.php';
$r = $conn->query('DESC users');
while($row = $r->fetch_assoc()) {
    echo $row['Field'] . ": " . $row['Null'] . " | ";
}
echo "\n";
$r = $conn->query('DESC doctor_profiles');
while($row = $r->fetch_assoc()) {
    echo $row['Field'] . ": " . $row['Null'] . " | ";
}
echo "\n";
$r = $conn->query('DESC consultations');
while($row = $r->fetch_assoc()) {
    echo $row['Field'] . ": " . $row['Null'] . " | ";
}
