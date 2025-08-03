<?php
/**
 * Fix syntax errors in manage_editions.php
 */

$file = 'admin/manage_editions.php';
$content = file_get_contents($file);

if ($content === false) {
    die("Could not read $file\n");
}

echo "Fixing syntax errors in $file...\n";

// Track changes
$changes = 0;

// Fix missing $ in (edition[...] patterns
$patterns = [
    '/\(edition\[/' => '($edition[',
];

foreach ($patterns as $pattern => $replacement) {
    $new_content = preg_replace($pattern, $replacement, $content);
    if ($new_content !== $content) {
        $changes += substr_count($content, $pattern) - substr_count($new_content, $pattern);
        $content = $new_content;
    }
}

// Fix the strtolower function call that has too many arguments
$content = str_replace(
    'htmlspecialchars(strtolower(($edition[\'title\'] ?? \'\'), ENT_QUOTES, \'UTF-8\'))',
    'htmlspecialchars(strtolower($edition[\'title\'] ?? \'\'), ENT_QUOTES, \'UTF-8\')',
    $content
);

// Count this change separately
if (strpos($content, 'strtolower($edition[\'title\'] ?? \'\'), ENT_QUOTES') !== false) {
    $changes++;
}

// Write the corrected content back
if (file_put_contents($file, $content) !== false) {
    echo "Successfully fixed $changes syntax errors in $file\n";
} else {
    echo "Failed to write corrected content to $file\n";
}

// Validate syntax
echo "Validating syntax...\n";
$output = [];
$return_var = 0;
exec("php -l $file 2>&1", $output, $return_var);

if ($return_var === 0) {
    echo "✅ $file syntax is now valid!\n";
} else {
    echo "❌ $file still has syntax errors:\n";
    foreach ($output as $line) {
        echo "   $line\n";
    }
}
?>
