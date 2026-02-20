<?php
$doctor_id = 1; // Assuming doctor ID 1 for now
require_once 'db.php';
$active = $conn->query("
    (SELECT c.id, CAST('consultation' AS CHAR) as type, CAST(c.status AS CHAR) as status
    FROM consultations c
    WHERE c.id = 10007)
    UNION ALL
    (SELECT a.id, CAST('appointment' AS CHAR) as type, CAST(a.status AS CHAR) as status
    FROM appointments a
    WHERE a.id = 10007)
");
while($row = $active->fetch_assoc()) {
    print_r($row);
}
?>
