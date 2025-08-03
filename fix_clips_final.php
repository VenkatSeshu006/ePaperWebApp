<?php
/**
 * Comprehensive fix for clips.php remaining syntax errors
 */

$file = 'admin/clips.php';
$content = file_get_contents($file);

if ($content === false) {
    die("Could not read $file\n");
}

echo "Comprehensive fix for remaining clips.php errors...\n";

$changes = 0;

// Fix specific patterns with exact string replacements
$fixes = [
    // Fix missing $ in variable names
    '(clip[' => '($clip[',
    
    // Fix file_exists calls
    'file_exists(\'../\' . (clip[\'file_path\'] ?? \'\')' => 'file_exists(\'../\' . ($clip[\'file_path\'] ?? \'\'))',
    'file_exists(\'../\' . (clip[\'thumbnail_path\'] ?? \'\')' => 'file_exists(\'../\' . ($clip[\'thumbnail_path\'] ?? \'\'))',
    
    // Fix htmlspecialchars calls
    'htmlspecialchars((clip[' => 'htmlspecialchars($clip[',
    
    // Fix other function calls with missing $
    'strtotime((clip[' => 'strtotime($clip[',
    'date(\'M j, Y\', strtotime((clip[' => 'date(\'M j, Y\', strtotime($clip[',
    
    // Fix if conditions
    '<?php if ((clip[' => '<?php if ($clip[',
    '<?php echo (clip[' => '<?php echo $clip[',
    
    // Fix onclick functions
    'editClip((clip[' => 'editClip($clip[',
    'deleteClip((clip[' => 'deleteClip($clip[',
    'showPreview(\'<?php echo htmlspecialchars((clip[' => 'showPreview(\'<?php echo htmlspecialchars($clip[',
];

foreach ($fixes as $search => $replace) {
    $newContent = str_replace($search, $replace, $content);
    if ($newContent !== $content) {
        $changes++;
        $content = $newContent;
        echo "Fixed: $search -> $replace\n";
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
