<?php
// Debug pharmacy API responses
session_start();
$_SESSION['user_id'] = 4;
$_SESSION['role'] = 'pharmacy';

ob_start();
$_GET['action'] = 'get_pending_prescriptions';
include 'pharmacy_api_enhanced.php';
$output = ob_get_clean();

echo "=== get_pending_prescriptions Response ===\n";
echo $output;
echo "\n\n";

ob_start();
$_GET['action'] = 'get_dashboard_stats';
include 'pharmacy_api_enhanced.php';
$output = ob_get_clean();

echo "=== get_dashboard_stats Response ===\n";
echo $output;
?>
