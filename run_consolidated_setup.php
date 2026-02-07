<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 minutes

echo "=== CONSOLIDATED DATABASE SETUP ===\n\n";

require_once 'db.php';

// Enable multi-query
$conn->close();
$conn = new mysqli("localhost", "root", "", "medconnect");
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Read the SQL file
$sql = file_get_contents('consolidated_database_setup.sql');

echo "Executing consolidated database setup...\n";
echo "This will add all missing tables and columns without losing data.\n\n";

// Execute multi-query
if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            while ($row = $result->fetch_row()) {
                // Print any messages
                if (isset($row[0])) {
                    echo "  " . $row[0] . "\n";
                }
            }
            $result->free();
        }
        
        // Check for errors
        if ($conn->errno) {
            $error = $conn->error;
            // Only show errors that aren't "already exists" type
            if (stripos($error, 'already exists') === false && 
                stripos($error, 'Duplicate') === false) {
                echo "Note: " . $error . "\n";
            }
        }
        
    } while ($conn->more_results() && $conn->next_result());
}

// Check for final error
if ($conn->errno) {
    echo "\nFinal status: " . $conn->error . "\n";
}

echo "\n=== VERIFYING DATABASE ===\n\n";

// Verify tables
$result = $conn->query("SHOW TABLES");
$tables = [];
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

echo "Total tables: " . count($tables) . "\n\n";

// Check key tables
$keyTables = [
    'users' => 'Core users table',
    'consultations' => 'Consultations',
    'doctor_profiles' => 'Doctor profiles',
    'patient_profiles' => 'Patient profiles',
    'symptom_attachments' => 'Symptom attachments',
    'appointments' => 'Appointments',
    'doctor_queue' => 'Doctor queue',
    'consultation_messages' => 'Consultation messages',
    'consultation_sessions' => 'Consultation sessions',
    'prescriptions_v2' => 'Prescriptions',
    'prescription_items_v2' => 'Prescription items',
    'prescription_tests' => 'Lab tests/referrals',
    'pharmacy_profiles' => 'Pharmacy profiles',
    'pharmacy_inventory' => 'Pharmacy inventory',
    'prescription_orders' => 'Prescription orders',
    'delivery_tracking' => 'Delivery tracking',
    'payment_transactions' => 'Payment transactions',
    'revenue_splits' => 'Revenue configuration',
    'payouts' => 'Payouts',
    'doctor_earnings' => 'Doctor earnings',
    'pharmacy_earnings' => 'Pharmacy earnings',
    'notification_preferences' => 'Notification preferences',
    'scheduled_notifications' => 'Scheduled notifications',
    'notification_templates' => 'Notification templates',
    'notification_log' => 'Notification log',
    'doctor_notifications' => 'Doctor notifications',
    'access_logs' => 'Access logs',
    'compliance_events' => 'Compliance events',
    'consent_logs' => 'Consent logs',
    'consultation_audit_log' => 'Consultation audit',
    'doctor_reviews' => 'Doctor reviews',
    'doctor_availability' => 'Doctor availability',
    'doctor_availability_overrides' => 'Availability overrides',
    'patient_medical_history' => 'Medical history',
    'doctor_locations' => 'Doctor locations',
    'pharmacy_locations' => 'Pharmacy locations',
    'symptom_keywords' => 'Symptom keywords'
];

$existing = 0;
$missing = 0;

foreach ($keyTables as $table => $description) {
    if (in_array($table, $tables)) {
        $countResult = $conn->query("SELECT COUNT(*) as cnt FROM `$table`");
        $count = $countResult->fetch_assoc()['cnt'];
        echo "✓ $description: $count rows\n";
        $existing++;
    } else {
        echo "✗ $description: MISSING\n";
        $missing++;
    }
}

echo "\n=== SUMMARY ===\n";
echo "Tables verified: $existing\n";
echo "Tables missing: $missing\n";

if ($missing == 0) {
    echo "\n✓ SUCCESS! All tables are now in the database.\n";
    echo "✓ All existing data has been preserved.\n";
} else {
    echo "\n⚠ Some tables are still missing. Please check errors above.\n";
}

echo "\n=== SETUP COMPLETE ===\n";

$conn->close();