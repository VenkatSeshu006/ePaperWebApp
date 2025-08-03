<?php
/**
 * Fix clips.php
 */

$file = 'admin/clips.php';
$content = file_get_contents($file);

if ($content === false) {
    die("Could not read $file\n");
}

echo "Fixing $file...\n";

// Fix malformed patterns
$content = str_replace('(int)$_POST[\'clip_id\'] ?? 0)', '(int)($_POST[\'clip_id\'] ?? 0)', $content);

// Write back
if (file_put_contents($file, $content) !== false) {
    echo "Applied fixes to $file\n";
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
