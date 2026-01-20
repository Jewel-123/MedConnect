<?php
require_once 'db.php';
$sql = file_get_contents('update_db_v3.sql');
$queries = explode(';', $sql);
$success = 0; $errors = 0;
foreach ($queries as $q) {
    if (trim($q)) {
        if ($conn->query($q)) $success++;
        else { echo "Err: " . $conn->error . "\n"; $errors++; }
    }
}
echo "Migration v3: $success ok, $errors errors.";
?>
