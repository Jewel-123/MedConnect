<?php
$files = ['auth.php', 'db.php', 'email_config.php'];
foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    $original = $content;
    
    // Remove BOM
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
    
    // Remove any whitespace before <?php
    $content = ltrim($content);
    
    // If the file ends with ?>, remove any whitespace after it
    if (strpos($content, '?>') !== false) {
        $content = preg_replace('/\?>\s+$/s', '?>', $content);
    }

    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "Cleaned $file\n";
    } else {
        echo "No issues found in $file\n";
    }
}