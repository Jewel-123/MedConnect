<?php
require_once 'db.php';

$table = $_GET['table'] ?? '';

if ($table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "Table '$table' exists";
    } else {
        echo "Table '$table' does not exist";
    }
} else {
    echo "No table specified";
}