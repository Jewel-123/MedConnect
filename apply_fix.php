<?php
include 'db.php';

// Fix API hardcoded email (it was pharmacy@medconnect.com which is correct, but let's be sure)
$apiFile = 'patient_prescription_api.php';
$content = file_get_contents($apiFile);
$correctEmail = 'pharmacy@medconnect.com';
$newContent = str_replace("'pharmacy@medconnect.com'", "'$correctEmail'", $content);
file_put_contents($apiFile, $newContent);

// Fix RX 6 data discrepancy
$queries = [
    "UPDATE prescriptions_v2 SET pharmacy_id = 4, status = 'Awaiting Payment', ordered_at = NOW() WHERE id = 6",
    "UPDATE prescription_orders SET pharmacy_id = 4, patient_id = 21, order_status = 'awaiting_payment', ordered_at = NOW() WHERE prescription_id = 6"
];

foreach ($queries as $q) {
    if ($conn->query($q)) {
        echo "Executed: $q\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
}

// Check if Jewel Biju has any other duplicate IDs
$res = $conn->query("SELECT id FROM users WHERE full_name LIKE '%JEWEL%'");
while($row = $res->fetch_assoc()) {
    echo "Found Jewel with ID: {$row['id']}\n";
}

echo "Fix applied.\n";
?>
