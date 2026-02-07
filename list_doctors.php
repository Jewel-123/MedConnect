<?php
require_once 'db.php';
$res=$conn->query("SELECT u.id, u.full_name, dp.specialization FROM users u JOIN doctor_profiles dp ON u.id=dp.user_id WHERE u.role='doctor'");
while($row=$res->fetch_assoc()){
    echo $row['id'].': '.$row['full_name'].' ('.$row['specialization'].')'."\n";
}