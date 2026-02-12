<?php
error_reporting(0);
$conn = new mysqli("localhost", "root", "");

echo "=== SEARCHING ALL DATABASES FOR YOUR DATA ===\n\n";

// Check medconnect
echo "1. Checking 'medconnect' database...\n";
$conn->select_db("medconnect");
$result = @$conn->query("SELECT COUNT(*) as cnt FROM users");
if ($result) {
    $count = $result->fetch_assoc()['cnt'];
    echo "   Total users: $count\n";
    if ($count > 0) {
        $users = $conn->query("SELECT name, email, role FROM users LIMIT 10");
        while ($u = $users->fetch_assoc()) {
            echo "   - {$u['name']} | {$u['email']} | {$u['role']}\n";
        }
    }
} else {
    echo "   No users table or database doesn't exist\n";
}

// Check medconnectnew
echo "\n2. Checking 'medconnectnew' database...\n";
$result = @$conn->query("SELECT COUNT(*) as cnt FROM medconnectnew.users");
if ($result) {
    $count = $result->fetch_assoc()['cnt'];
    echo "   Total users: $count\n";
    if ($count > 0) {
        $users = $conn->query("SELECT name, email, role FROM medconnectnew.users LIMIT 10");
        while ($u = $users->fetch_assoc()) {
            echo "   - {$u['name']} | {$u['email']} | {$u['role']}\n";
        }
    }
} else {
    echo "   No users table or database doesn't exist\n";
}

// Check medconnect_new
echo "\n3. Checking 'medconnect_new' database...\n";
$result = @$conn->query("SELECT COUNT(*) as cnt FROM medconnect_new.users");
if ($result) {
    $count = $result->fetch_assoc()['cnt'];
    echo "   Total users: $count\n";
    if ($count > 0) {
        $users = $conn->query("SELECT name, email, role FROM medconnect_new.users LIMIT 10");
        while ($u = $users->fetch_assoc()) {
            echo "   - {$u['name']} | {$u['email']} | {$u['role']}\n";
        }
    }
} else {
    echo "   No users table or database doesn't exist\n";
}

echo "\n=== SEARCH COMPLETE ===\n";
echo "\nIf you see your data above (like smith@gmail.com), let me know which database it's in!\n";
echo "If no data found, unfortunately it may have been permanently deleted.\n";
?>
