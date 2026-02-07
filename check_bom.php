<?php
$files = ['auth.php', 'db.php', 'email_config.php'];
foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    $firstChars = substr($content, 0, 5);
    echo "$file: First 5 bytes: " . bin2hex($firstChars) . " (Decoded: $firstChars)\n";
    if (trim(substr($content, 0, 5)) !== '<?php' && strpos($content, '<?php') !== 0) {
        echo "WARNING: $file has content before <?php\n";
    }
}