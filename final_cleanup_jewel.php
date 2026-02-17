<?php
$conn = new mysqli("localhost", "root", "", "medconnect");
if ($conn->connect_error) die("Connection failed");

$user_res = $conn->query("SELECT id FROM users WHERE full_name = 'JEWEL BIJU'");
$ids = [];
while($row = $user_res->fetch_assoc()) {
    $ids[] = $row['id'];
}

if (empty($ids)) {
    die("No user found with name JEWEL BIJU");
}

$ids_str = implode(',', $ids);
echo "User IDs for JEWEL BIJU: $ids_str\n";

$conn->query("DELETE FROM appointments WHERE patient_id IN ($ids_str)");
echo "Deleted " . $conn->affected_rows . " appointments.\n";

$conn->close();
?>
