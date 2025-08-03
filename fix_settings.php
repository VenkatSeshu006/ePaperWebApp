<?php
/**
 * Fix settings.php
 */

$file = 'admin/settings.php';
$content = file_get_contents($file);

if ($content === false) {
    die("Could not read $file\n");
}

echo "Fixing $file...\n";

// Fix malformed patterns
$patterns = [
    '\?\? \'\'\) \?\? \'\'' => '\?\? \'\'',
    '\?\? 12\)\,' => '\?\? 12,',
    '\?\? 1\)\,' => '\?\? 1,',
    '\?\? true\)\,' => '\?\? true,',
    '\?\? false\)\,' => '\?\? false,',
];

$changes = 0;
foreach ($patterns as $pattern => $replacement) {
    $newContent = preg_replace('/' . $pattern . '/', $replacement, $content);
    if ($newContent !== null && $newContent !== $content) {
        $changes++;
        $content = $newContent;
    }
}

// Specific fixes
$specificFixes = [
    '(int)$_POST[\'items_per_page\'] ?? 12),' => '(int)($_POST[\'items_per_page\'] ?? 12),',
    '$_POST[\'enable_comments\'] ?? true),' => '$_POST[\'enable_comments\'] ?? true,',
    '$_POST[\'maintenance_mode\'] ?? false),' => '$_POST[\'maintenance_mode\'] ?? false,',
];

foreach ($specificFixes as $search => $replace) {
    if (strpos($content, $search) !== false) {
        $content = str_replace($search, $replace, $content);
        $changes++;
    }
}

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
