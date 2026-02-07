<?php
require_once 'db.php';

// Set recent consultations to paid so they show up for the user
$ids = [15, 16, 17];
foreach ($ids as $id) {
    $conn->query("UPDATE consultations SET payment_status = 'paid' WHERE id = $id");
}

echo "Updated " . count($ids) . " consultations to paid status.";