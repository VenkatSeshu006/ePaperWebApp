<?php
/**
 * Dashboard Syntax Fix Script
 * Corrects incorrectly applied automatic fixes
 */

echo "Fixing dashboard.php syntax errors...\n";

$file = 'admin/dashboard.php';
$content = file_get_contents($file);

// Fix incorrect variable wrapping
$fixes = [
    // Fix stats array access
    '(stats[' => '$stats[',
    // Fix $_SERVER, $_SESSION, $_GET, $_POST access
    '(_SERVER[' => '$_SERVER[',
    '(_SESSION[' => '$_SESSION[',
    '(_GET[' => '$_GET[',
    '(_POST[' => '$_POST[',
    // Fix $edition array access in foreach loops
    '(edition[' => '$edition[',
    // Fix isset() calls with null coalescing
    'isset((' => 'isset(',
    ')) ?' => ') ?',
    // Remove double null coalescing
    '\?\? \'\'\) \?\?' => '??',
];

foreach ($fixes as $search => $replace) {
    $content = str_replace($search, $replace, $content);
}

// Fix specific problematic patterns
$content = preg_replace('/\(\$([a-zA-Z_][a-zA-Z0-9_]*)\[([\'"][^\'"]+[\'"])\] \?\? [\'"][^\'"]*[\'"\s]*\)/', '$$1[$2]', $content);

// Fix assignment operators that were incorrectly wrapped
$content = preg_replace('/\(\$([a-zA-Z_][a-zA-Z0-9_]*)\[([\'"][^\'"]+[\'"])\] \?\? [\'"][^\'"]*[\'"\s]*\) =/', '$$1[$2] =', $content);

file_put_contents($file, $content);

echo "Dashboard syntax fixes applied!\n";

// Test syntax
$output = shell_exec('php -l ' . $file . ' 2>&1');
if (strpos($output, 'No syntax errors') !== false) {
    echo "✓ Syntax check passed!\n";
} else {
    echo "⚠ Syntax errors remain:\n$output\n";
}
?>
