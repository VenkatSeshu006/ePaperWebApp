<?php
/**
 * Simple syntax pattern fix for admin files
 */

$files = ['admin/settings.php', 'admin/categories.php', 'admin/clips.php'];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    
    echo "Processing $file...\n";
    $content = file_get_contents($file);
    
    // Fix common malformed patterns
    $content = preg_replace('/isset\$_([A-Z]+)\[/', 'isset($_$1[', $content);
    $content = preg_replace('/\$_([A-Z]+)\[([\'"][^\'"]+[\'"])\] \?\? [\'"][^\'"]*[\'"\s]*\)\) \?\? [\'"][^\'"]*[\'"\s]*,/', '$_$1[$2] ?? \'\',', $content);
    $content = preg_replace('/\?\? [\'"][^\'"]*[\'"\s]*\)\) \?\? [\'"][^\'"]*[\'"\s]*/', '?? \'\'', $content);
    $content = preg_replace('/\)\)\)/', '))', $content);
    
    file_put_contents($file, $content);
    
    $output = shell_exec("php -l $file 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "✓ Fixed $file\n";
    } else {
        echo "Still errors in $file\n";
    }
}
?>
