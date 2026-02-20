<?php
require_once 'db.php';
$res = $conn->query("SELECT doctor_id FROM consultations WHERE status IN ('accepted', 'confirmed', 'in_progress', 'paused') LIMIT 1");
if ($row = $res->fetch_assoc()) {
    echo $row['doctor_id'];
} else {
    echo "0";
}
?>
