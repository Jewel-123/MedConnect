<?php
// Simulate fetch from doctor's perspective (ID 25)
session_start();
$_SESSION['user_id'] = 25;
$_SESSION['role'] = 'doctor';

$_GET['action'] = 'fetch';
$_GET['consultation_id'] = 40;
$_GET['last_id'] = 0;

echo "=== Simulating Fetch for Doctor ===\n";
include 'chat_api.php';
echo "\n";
