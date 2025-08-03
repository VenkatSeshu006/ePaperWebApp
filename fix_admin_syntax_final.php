<?php
/**
 * Comprehensive Admin Files Syntax Fix
 * Fixes malformed isset() and variable access patterns
 */

echo "Fixing admin files syntax errors...\n";

$admin_files = glob('admin/*.php');

foreach ($admin_files as $file) {
    echo "Processing: $file\n";
    
    $content = file_get_contents($file);
    $original = $content;
    
    // Fix malformed isset() patterns
    $content = preg_replace('/isset\(\((\$_[A-Z]+\[[^\]]+\]) \?\? [\'"][^\'"]*[\'"\s]*\)\)/', 'isset($1)', $content);
    
    // Fix malformed variable access in conditions
    $content = preg_replace('/\(\((\$_[A-Z]+\[[^\]]+\]) \?\? [\'"][^\'"]*[\'"\s]*\)\)/', '$1', $content);
    
    // Fix $_SERVER, $_SESSION, $_GET, $_POST access
    $content = str_replace('(_SERVER[', '$_SERVER[', $content);
    $content = str_replace('(_SESSION[', '$_SESSION[', $content);
    $content = str_replace('(_GET[', '$_GET[', $content);
    $content = str_replace('(_POST[', '$_POST[', $content);
    $content = str_replace('(_FILES[', '$_FILES[', $content);
    $content = str_replace('(_REQUEST[', '$_REQUEST[', $content);
    
    // Fix double null coalescing
    $content = preg_replace('/\?\? [\'"][^\'"]*[\'"\s]*\)\) \?\? [\'"][^\'"]*[\'"\s]*/', '?? \'\'', $content);
    
    // Remove extra parentheses from conditions
    $content = preg_replace('/\(\((\$[a-zA-Z_][a-zA-Z0-9_]*)\) \?\? [\'"][^\'"]*[\'"\s]*\)/', '$1', $content);
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "✓ Fixed $file\n";
    } else {
        echo "- No changes needed in $file\n";
    }
    
    // Test syntax
    $output = shell_exec("php -l \"$file\" 2>&1");
    if (strpos($output, 'No syntax errors') === false) {
        echo "⚠ Syntax errors still exist in $file:\n";
        echo $output . "\n";
    }
}

echo "\nAll admin files processed!\n";
?>
