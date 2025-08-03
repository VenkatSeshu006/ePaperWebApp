<?php
/**
 * Fix clips.php database column references
 */

$file = 'admin/clips.php';
$content = file_get_contents($file);

if ($content === false) {
    die("Could not read $file\n");
}

echo "Fixing clips.php database column references...\n";

$changes = 0;

// Replace file_path with image_path
$content = str_replace('file_path', 'image_path', $content);
$changes++;

// Remove PDO::FETCH_ASSOC from fetch() calls since it seems to be causing issues
$content = str_replace('->fetch(PDO::FETCH_ASSOC)', '->fetch()', $content);
$changes++;

// Write back
if (file_put_contents($file, $content) !== false) {
    echo "Applied $changes fixes to $file\n";
} else {
    echo "Failed to write to $file\n";
}

// Check syntax
$output = [];
$return_var = 0;
exec("php -l $file 2>&1", $output, $return_var);

if ($return_var === 0) {
    echo "✅ $file syntax is valid\n";
} else {
    echo "❌ $file still has errors:\n";
    foreach ($output as $line) {
        echo "   $line\n";
    }
}
?>
