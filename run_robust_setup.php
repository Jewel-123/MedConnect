<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once 'db.php';

echo "=== ROBUST CONSOLIDATED SETUP ===\n\n";

$sqlFile = 'consolidated_database_setup.sql';
if (!file_exists($sqlFile)) {
    die("Error: $sqlFile not found.\n");
}

try {
    $sql = file_get_contents($sqlFile);

    // Remove comments
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

    echo "Executing SQL using multi_query...\n";

    if ($conn->multi_query($sql)) {
        $i = 0;
        do {
            $i++;
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
        echo "✓ All statements executed successfully!\n";
    }
} catch (mysqli_sql_exception $e) {
    echo "\n✗ MySQL Error: " . $e->getMessage() . "\n";
    // Don't stop here, let's see which tables exist
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== VERIFYING TABLES ===\n";
$tables = ['users', 'doctor_profiles', 'patient_profiles', 'appointments', 'consultations', 'prescriptions_v2', 'revenue_splits', 'doctor_availability'];
foreach ($tables as $table) {
    try {
        $res = $conn->query("SHOW TABLES LIKE '$table'");
        if ($res && $res->num_rows > 0) {
            $countRes = $conn->query("SELECT COUNT(*) as cnt FROM `$table` ");
            $count = $countRes ? $countRes->fetch_assoc()['cnt'] : "ERR";
            echo "✓ Table '$table' exists (Rows: $count)\n";
        } else {
            echo "✗ Table '$table' is MISSING!\n";
        }
    } catch (Exception $e) {
        echo "✗ Error checking table '$table': " . $e->getMessage() . "\n";
    }
}

echo "\n=== SETUP COMPLETE ===\n";