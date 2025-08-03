<?php
/**
 * Comprehensive Admin Files Syntax Fix
 * Fixes syntax errors introduced by the automatic warning fix script
 */

echo "Fixing syntax errors in admin files...\n";

$files = ['admin/settings.php', 'admin/categories.php', 'admin/clips.php'];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "File not found: $file\n";
        continue;
    }
    
    echo "Fixing: $file\n";
    $content = file_get_contents($file);
    
    // Fix the most common issues
    $fixes = [
        // Fix isset() calls with null coalescing
        'isset(($_SESSION[' => 'isset($_SESSION[',
        'isset(($_GET[' => 'isset($_GET[',
        'isset(($_POST[' => 'isset($_POST[',
        'isset(($_SERVER[' => 'isset($_SERVER[',
        'isset(($_REQUEST[' => 'isset($_REQUEST[',
        'isset(($_FILES[' => 'isset($_FILES[',
        'isset(($_COOKIE[' => 'isset($_COOKIE[',
        
        // Fix variable access
        '($_SESSION[' => '$_SESSION[',
        '($_GET[' => '$_GET[',
        '($_POST[' => '$_POST[',
        '($_SERVER[' => '$_SERVER[',
        '($_REQUEST[' => '$_REQUEST[',
        '($_FILES[' => '$_FILES[',
        '($_COOKIE[' => '$_COOKIE[',
        
        // Fix array access
        ')) ??' => ') ??',
        
        // Fix function calls
        'number_format(' => 'number_format(',
        'htmlspecialchars(' => 'htmlspecialchars(',
        'empty(' => 'empty(',
        'count(' => 'count(',
        'date(' => 'date(',
        'strtotime(' => 'strtotime(',
    ];
    
    foreach ($fixes as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }
    
    // Fix specific patterns with regex
    $content = preg_replace('/\(\$([a-zA-Z_][a-zA-Z0-9_]*)\[([\'"][^\'"]+[\'"])\] \?\? [\'"][^\'"]*[\'"\s]*\)/', '$$1[$2]', $content);
    $content = preg_replace('/\(\$([a-zA-Z_][a-zA-Z0-9_]*) \?\? [\'"][^\'"]*[\'"\s]*\)/', '$$1', $content);
    
    file_put_contents($file, $content);
    
    // Test syntax
    $output = shell_exec("php -l $file 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "✓ $file syntax fixed\n";
    } else {
        echo "⚠ $file still has errors:\n";
        echo $output . "\n";
    }
}

echo "\nAdmin files syntax fix completed!\n";
?>
