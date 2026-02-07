<?php
$lines = file('c:\xampp\htdocs\medconnect\doctor_api.php');
foreach ($lines as $i => $line) {
    if (preg_match("/case\s+'([^']+)'/", $line, $matches)) {
        echo ($i + 1) . ": case '" . $matches[1] . "'\n";
    }
}