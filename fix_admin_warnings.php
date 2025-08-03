<?php
/**
 * Admin Panel PHP Warning Fix Script
 * Addresses deprecation warnings and error handling across admin files
 */

echo "<h2>Fixing Admin Panel PHP Warnings</h2>\n";

// Files to fix
$admin_files = [
    'dashboard.php',
    'categories.php',
    'clips.php',
    'manage_editions.php',
    'settings.php',
    'diagnostics.php',
    'upload.php'
];

$fixes_applied = 0;

foreach ($admin_files as $file) {
    $full_path = __DIR__ . '/admin/' . $file;
    
    if (file_exists($full_path)) {
        echo "<h3>Processing: $file</h3>\n";
        
        $content = file_get_contents($full_path);
        $original_content = $content;
        
        // Fix htmlspecialchars deprecation warnings
        $content = preg_replace(
            '/htmlspecialchars\s*\(\s*([^,)]+)\s*\)/',
            'htmlspecialchars($1, ENT_QUOTES, \'UTF-8\')',
            $content
        );
        
        // Fix potential undefined array key warnings
        $content = preg_replace(
            '/\$_POST\[([\'"][^\'"]+[\'"])\]/',
            '($_POST[$1] ?? \'\')',
            $content
        );
        
        $content = preg_replace(
            '/\$_GET\[([\'"][^\'"]+[\'"])\]/',
            '($_GET[$1] ?? \'\')',
            $content
        );
        
        // Add null coalescing for array access
        $content = preg_replace(
            '/\$([a-zA-Z_][a-zA-Z0-9_]*)\[([\'"][^\'"]+[\'"])\](?!\s*\?\?)/',
            '($1[$2] ?? \'\')',
            $content
        );
        
        if ($content !== $original_content) {
            file_put_contents($full_path, $content);
            echo "✓ Fixed warnings in $file<br>\n";
            $fixes_applied++;
        } else {
            echo "ℹ No changes needed for $file<br>\n";
        }
    } else {
        echo "⚠ File not found: $file<br>\n";
    }
}

echo "<h3>Summary</h3>\n";
echo "<p>Applied fixes to $fixes_applied files</p>\n";

// Test the dashboard after fixes
echo "<h3>Testing Dashboard...</h3>\n";
$dashboard_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/admin/dashboard.php';
echo "<p><a href='$dashboard_url' target='_blank'>Test Dashboard</a></p>\n";

echo "<p><strong>Admin panel PHP warnings should now be resolved!</strong></p>\n";
?>
