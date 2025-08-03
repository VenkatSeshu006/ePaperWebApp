<?php
/**
 * Comprehensive fix for malformed PHP patterns in admin files
 */

$files = [
    'admin/categories.php',
    'admin/clips.php', 
    'admin/settings.php'
];

$totalChanges = 0;

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "⏭️  Skipping $file (not found)\n";
        continue;
    }
    
    echo "🔧 Fixing $file...\n";
    
    $content = file_get_contents($file);
    if ($content === false) {
        echo "❌ Could not read $file\n";
        continue;
    }
    
    $original = $content;
    $changes = 0;
    
    // Fix malformed patterns
    $patterns = [
        // Fix double ?? operators
        '/\?\? \'\'\) \?\? \'/' => '\?\? \'',
        '/\?\? \'\'\) \?\? \'\'\)/' => '\?\? \'\')',
        '/\?\? \'\'\) \?\? /' => '\?\? ',
        
        // Fix trim function calls
        '/trim\$_POST\[/' => 'trim($_POST[',
        '/trim\$_GET\[/' => 'trim($_GET[',
        
        // Fix comparison operators with ?? 
        '/\$_SERVER\[\'REQUEST_METHOD\'\] \?\? \'\'\) ==/' => '$_SERVER[\'REQUEST_METHOD\'] ==',
        '/\$_SERVER\[\'REQUEST_METHOD\'\] \?\? \'\'\) ===/' => '$_SERVER[\'REQUEST_METHOD\'] ===',
        
        // Fix malformed variable assignments
        '/\$([a-zA-Z_][a-zA-Z0-9_]*) = \$_POST\[\'([^\']+)\'\] \?\? \'\'\) \?\? /' => '$\1 = $_POST[\'\2\'] ?? ',
        '/\$([a-zA-Z_][a-zA-Z0-9_]*) = \$_GET\[\'([^\']+)\'\] \?\? \'\'\) \?\? /' => '$\1 = $_GET[\'\2\'] ?? ',
    ];
    
    foreach ($patterns as $pattern => $replacement) {
        $newContent = preg_replace('/' . $pattern . '/', $replacement, $content);
        if ($newContent !== null && $newContent !== $content) {
            $changes++;
            $content = $newContent;
        }
    }
    
    // Additional specific fixes
    $specificFixes = [
        '\$_POST[\'action\'] ?? \'\') ?? \'\'' => '$_POST[\'action\'] ?? \'\'',
        '\$_POST[\'name\'] ?? \'\')' => '$_POST[\'name\'] ?? \'\')',
        '\$_POST[\'description\'] ?? \'\')' => '$_POST[\'description\'] ?? \'\')',
        '\$_POST[\'color\'] ?? \'\') ?? \'#007bff\'' => '$_POST[\'color\'] ?? \'#007bff\'',
        'trim($_POST[\'name\'] ?? \'\')' => 'trim($_POST[\'name\'] ?? \'\')',
        'trim($_POST[\'description\'] ?? \'\')' => 'trim($_POST[\'description\'] ?? \'\')',
    ];
    
    foreach ($specificFixes as $search => $replace) {
        if (strpos($content, $search) !== false) {
            $content = str_replace($search, $replace, $content);
            $changes++;
        }
    }
    
    if ($content !== $original) {
        if (file_put_contents($file, $content) !== false) {
            echo "✅ Applied $changes fixes to $file\n";
            $totalChanges += $changes;
        } else {
            echo "❌ Failed to write to $file\n";
        }
    } else {
        echo "ℹ️  No changes needed in $file\n";
    }
    
    // Validate syntax
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
    echo "\n";
}

echo "🎉 Total fixes applied: $totalChanges\n";
?>
