<?php
/**
 * Fix remaining admin files with isset() syntax errors
 */

$files = [
    'admin/categories.php',
    'admin/clips.php', 
    'admin/diagnostics.php',
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
    
    // Fix malformed authentication check
    $patterns = [
        // Fix isset() with ?? operator
        '/if \(!isset\(\$_SESSION\[\'admin_logged_in\'\] \?\? \'\'\)\) \|\| \$_SESSION\[\'admin_logged_in\'\] \?\? \'\'\) !== true\)/' => 'if (!isset($_SESSION[\'admin_logged_in\']) || $_SESSION[\'admin_logged_in\'] !== true)',
        
        // Fix other isset() patterns  
        '/isset\(\$_GET\[\'logout\'\] \?\? \'\'\)/' => 'isset($_GET[\'logout\'])',
        '/isset\(\$_POST\[\'([^\']+)\'\] \?\? \'\'\)/' => 'isset($_POST[\'$1\'])',
        '/isset\(\$_GET\[\'([^\']+)\'\] \?\? \'\'\)/' => 'isset($_GET[\'$1\'])',
    ];
    
    foreach ($patterns as $pattern => $replacement) {
        $newContent = preg_replace($pattern, $replacement, $content);
        if ($newContent !== $content) {
            $changeCount = preg_match_all($pattern, $content);
            $changes += $changeCount;
            $content = $newContent;
        }
    }
    
    if ($content !== $original) {
        if (file_put_contents($file, $content) !== false) {
            echo "✅ Fixed $changes syntax errors in $file\n";
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
