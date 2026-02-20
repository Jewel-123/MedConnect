<?php
require_once 'db.php';

$patientIds = [21, 43];

echo "--- Searching for any symptoms/notes for Patient 21/43 ---\n";

function searchTable($conn, $table, $idCol, $ids) {
    echo "\nTable: $table\n";
    $idList = implode(',', $ids);
    $res = $conn->query("SELECT * FROM $table WHERE $idCol IN ($idList) ORDER BY id DESC LIMIT 50");
    if (!$res) {
        echo "Error in $table: " . $conn->error . "\n";
        return;
    }
    while ($row = $res->fetch_assoc()) {
        $found = false;
        foreach ($row as $col => $val) {
            if ($val && (stripos($val, 'symptom') !== false || stripos($val, 'fever') !== false || stripos($val, 'pain') !== false || stripos($col, 'note') !== false || stripos($col, 'symptom') !== false)) {
                echo "Match in $table (ID: {$row['id']}), Col $col: $val\n";
                $found = true;
            }
        }
    }
}

searchTable($conn, 'appointments', 'patient_id', $patientIds);
searchTable($conn, 'consultations', 'patient_id', $patientIds);
searchTable($conn, 'patient_profiles', 'user_id', $patientIds);

?>
