<?php
require_once 'db.php';

$searchTerms = ['fever', 'headache', 'pain', 'symptom']; // Add common terms if needed
$patientIds = [21, 43];

echo "--- Searching for any symptoms/notes for Patient 21/43 ---\n";

$tables = ['appointments', 'consultations', 'patient_profiles'];
foreach ($tables as $table) {
    echo "\nTable: $table\n";
    $res = $conn->query("SELECT * FROM $table WHERE patient_id IN (21, 43) OR (patient_id IS NULL AND id > 0) LIMIT 100");
    while ($row = $res->fetch_assoc()) {
        foreach ($row as $col => $val) {
            if (stripos($val, 'symptom') !== false || stripos($val, 'fever') !== false || stripos($val, 'pain') !== false || stripos($col, 'note') !== false || stripos($col, 'symptom') !== false) {
                echo "Match in $table (ID: {$row['id']}), Col $col: $val\n";
            }
        }
    }
}

// Special check for user 21's latest activities
echo "\n--- Latest 10 activities for User 21 ---\n";
$res = $conn->query("SELECT * FROM appointments WHERE patient_id = 21 ORDER BY id DESC LIMIT 10");
while ($row = $res->fetch_assoc()) {
    echo "Appt ID: {$row['id']} | Notes: '{$row['notes']}' | SymptomDesc: '{$row['symptom_description']}'\n";
}

?>
