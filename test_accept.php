<?php
session_start();
require_once 'db.php';

// Simulate doctor login
$_SESSION['user_id'] = 1; // Assuming doctor ID 1 exists
$_SESSION['role'] = 'doctor';

// Simulate accept consultation request
$_POST['action'] = 'accept_consultation';
$_POST['consultation_id'] = 46; // The test consultation we created

// Include the API
include 'doctor_api.php';
