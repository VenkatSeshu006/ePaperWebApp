<?php
/**
 * Simple fix for categories.php
 */

$file = 'admin/categories.php';
$content = file_get_contents($file);

if ($content === false) {
    die("Could not read $file\n");
}

echo "Fixing $file...\n";

// Simple string replacements
$fixes = [
    '(category[' => '($category[',
    'json_encode($category, ENT_QUOTES, \'UTF-8\')' => 'json_encode($category)',
    'addslashes((category[\'name\'] ?? \'\'), ENT_QUOTES, \'UTF-8\')' => 'addslashes($category[\'name\'] ?? \'\')',
];

$changes = 0;
foreach ($fixes as $search => $replace) {
    $newContent = str_replace($search, $replace, $content);
    if ($newContent !== $content) {
        $changes++;
        $content = $newContent;
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
