<?php
$content = file_get_contents('c:\xampp\htdocs\medconnect\doctor_api.php');
preg_match_all('/case\s+\'([^\']+)\'/', $content, $matches);
$counts = array_count_values($matches[1]);
foreach ($counts as $case => $count) {
    if ($count > 1) {
        echo "DUPLICATE: $case occurs $count times\n";
    } else {
        echo "UNIQUE: $case\n";
    }
}