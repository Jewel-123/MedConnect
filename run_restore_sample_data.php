<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db.php';

echo "Restoring sample data...\n";

$sql = file_get_contents('restore_sample_data.sql');

if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            while ($row = $result->fetch_row()) {
                if (isset($row[0])) echo $row[0] . "\n";
            }
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
}

if ($conn->errno) {
    echo "Error: " . $conn->error . "\n";
} else {
    echo "Sample data restored successfully.\n";
}

// Verify counts again
$tables=['users','consultations','appointments','prescriptions_v2','doctor_profiles', 'pharmacy_profiles'];
echo "\nVerifying item counts:\n";
foreach($tables as $t) {
    $r=$conn->query("SELECT COUNT(*) FROM $t");
    if($r) {
        echo "$t: ".$r->fetch_row()[0]."\n";
    }
}