<?php
/**
 * Comprehensive PHP file cleaner
 * Removes all trailing whitespace and closing ?> tags from PHP files
 */

$directory = __DIR__;
$files = glob($directory . '/*.php');

echo "=== PHP File Cleanup Report ===\n\n";

$fixed = 0;
$alreadyClean = 0;

foreach ($files as $file) {
    $basename = basename($file);
    
    // Skip this script itself
    if ($basename === 'cleanup_php_files.php') {
        continue;
    }
    
    $content = file_get_contents($file);
    $original = $content;
    
    // Remove trailing whitespace (spaces, tabs, newlines, carriage returns)
    $content = rtrim($content);
    
    // Remove closing PHP tags from pure PHP files
    if (substr($content, 0, 5) === '<?php' && !preg_match('/<\?php[\s\S]*\?>[\s\S]*</', $content)) {
        // This is a pure PHP file (no HTML mixed in)
        if (substr($content, -2) === '?>') {
            $content = substr($content, 0, -2);
            $content = rtrim($content);
            echo "✓ Removed closing ?> tag from: $basename\n";
        }
    }
    
    // Check if changes were made
    if ($content !== $original) {
        file_put_contents($file, $content);
        $fixed++;
        echo "✓ Fixed trailing whitespace in: $basename\n";
    } else {
        $alreadyClean++;
    }
}

echo "\n=== Summary ===\n";
echo "Fixed: $fixed files\n";
echo "Already clean: $alreadyClean files\n";
echo "\n✅ Cleanup complete!\n";
