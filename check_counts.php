<?php
require_once 'db.php';
$tables=['users','consultations','appointments','prescriptions_v2','doctor_profiles', 'pharmacy_profiles'];
echo "Checking item counts:\n";
foreach($tables as $t) {
    $r=$conn->query("SELECT COUNT(*) FROM $t");
    if($r) {
        echo "$t: ".$r->fetch_row()[0]."\n";
    } else {
        echo "$t: Error - ".$conn->error."\n";
    }
}