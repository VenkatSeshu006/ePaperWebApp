<?php
/**
 * Comprehensive fix for clips.php malformed patterns
 */

$file = 'admin/clips.php';
$content = file_get_contents($file);

if ($content === false) {
    die("Could not read $file\n");
}

echo "Comprehensive fix for $file...\n";

// Fix all malformed patterns
$patterns = [
    '\?\? \'\'\) \?\? \[\]' => '?? []',
    '\?\? \'\'\) \?\? \'\'' => '?? \'\'',
    '\?\? \'\'\) \?\? ' => '?? ',
    'trim\$_POST\[' => 'trim($_POST[',
    'trim\$_GET\[' => 'trim($_GET[',
    '\$_GET\[\'([^\']+)\'\] \?\? \'\'\) \?\? \'\'' => '$_GET[\'$1\'] ?? \'\'',
    '\$_POST\[\'([^\']+)\'\] \?\? \'\'\) \?\? \'\'' => '$_POST[\'$1\'] ?? \'\'',
];

$changes = 0;
foreach ($patterns as $pattern => $replacement) {
    $newContent = preg_replace('/' . $pattern . '/', $replacement, $content);
    if ($newContent !== null && $newContent !== $content) {
        $changes++;
        $content = $newContent;
    }
}

// Additional specific fixes
$specificFixes = [
    '$_GET[\'edition\'] ?? \'\') ?? \'\'' => '$_GET[\'edition\'] ?? \'\'',
    '$_GET[\'search\'] ?? \'\') ?? \'\'' => '$_GET[\'search\'] ?? \'\'',
    '$_GET[\'page\'] ?? \'\') ?? 1' => '$_GET[\'page\'] ?? 1',
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
