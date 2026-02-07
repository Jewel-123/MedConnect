<?php
require_once 'db.php';

// Check unassigned pending
$res = $conn->query("SELECT COUNT(*) as count FROM consultations WHERE status = 'pending' AND doctor_id IS NULL");
$unassigned = $res->fetch_assoc()['count'];
echo "UNASSIGNED PENDING: $unassigned\n";

// Check all pending
$res = $conn->query("SELECT COUNT(*) as count FROM consultations WHERE status = 'pending'");
$all_pending = $res->fetch_assoc()['count'];
echo "ALL PENDING: $all_pending\n";

// Check with details
$res = $conn->query("SELECT id, status, doctor_id, matched_specialty FROM consultations WHERE status = 'pending' LIMIT 5");
echo "\nPENDING DETAILS:\n";
while ($row = $res->fetch_assoc()) {
    print_r($row);
}