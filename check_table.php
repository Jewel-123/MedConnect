<?php
require_once 'db.php';
echo $conn->query("DESCRIBE doctor_earnings") ? "YES" : "NO";