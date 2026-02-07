<?php
echo "Checking mysqli extension...\n";
if (extension_loaded('mysqli')) {
    echo "mysqli is LOADED.\n";
    try {
        $conn = new mysqli('localhost', 'root', '', 'medconnect');
        if ($conn->connect_error) {
            echo "Connection failed: " . $conn->connect_error . "\n";
        } else {
            echo "Connection successful!\n";
            echo "Server info: " . $conn->server_info . "\n";
        }
    } catch (Throwable $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
} else {
    echo "mysqli is NOT LOADED.\n";
    echo "Configuration file: " . php_ini_loaded_file() . "\n";
    echo "Extension dir: " . ini_get('extension_dir') . "\n";
}