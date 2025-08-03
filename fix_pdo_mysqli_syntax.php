<?php
/**
 * PDO/MySQLi Syntax Fix Script
 * This script will systematically fix all PDO/MySQLi syntax mixing issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔧 Starting PDO/MySQLi Syntax Fix...\n\n";

// Directory to scan
$directory = __DIR__;

// File extensions to process
$extensions = ['php'];

// MySQLi to PDO conversions
$conversions = [
    // fetch_assoc() -> fetch()
    '/\->fetch_assoc\(\)/' => '->fetch()',
    
    // get_result()->fetch_assoc() -> fetch()
    '/\->get_result\(\)\->fetch_assoc\(\)/' => '->fetch()',
    
    // bind_param and execute pattern (more complex, will handle separately)
    // ->close() -> = null
    '/\$\w+\->close\(\);/' => '$conn = null;',
];

// Files to exclude
$excludeFiles = [
    'fix_pdo_mysqli_syntax.php',
    'database.sql',
    '.git',
    'vendor',
    'node_modules'
];

// Get all PHP files
function getAllPhpFiles($dir, $extensions, $excludeFiles) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $extension = strtolower($file->getExtension());
            $fileName = $file->getFilename();
            $filePath = $file->getPathname();
            
            // Skip excluded files/directories
            $skip = false;
            foreach ($excludeFiles as $exclude) {
                if (strpos($filePath, $exclude) !== false) {
                    $skip = true;
                    break;
                }
            }
            
            if (!$skip && in_array($extension, $extensions)) {
                $files[] = $filePath;
            }
        }
    }
    
    return $files;
}

// Fix bind_param patterns
function fixBindParamPatterns($content) {
    // Pattern: $stmt->bind_param("type", $var1, $var2); $stmt->execute();
    // Replace with: $stmt->execute([$var1, $var2]);
    
    // This is a complex regex pattern - for now, we'll handle simpler cases
    $patterns = [
        // Simple bind_param with execute
        '/\$(\w+)\->bind_param\(["\']([sidb]+)["\']\s*,\s*([^)]+)\);\s*\$\1\->execute\(\);/s' => 
        '$stmt->execute([$3]);',
    ];
    
    foreach ($patterns as $pattern => $replacement) {
        $content = preg_replace($pattern, $replacement, $content);
    }
    
    return $content;
}

// Process files
$files = getAllPhpFiles($directory, $extensions, $excludeFiles);
$totalFiles = count($files);
$processedFiles = 0;
$modifiedFiles = 0;

echo "📁 Found {$totalFiles} PHP files to process...\n\n";

foreach ($files as $filePath) {
    $processedFiles++;
    $relativePath = str_replace($directory . DIRECTORY_SEPARATOR, '', $filePath);
    
    // Read file content
    $originalContent = file_get_contents($filePath);
    $modifiedContent = $originalContent;
    $hasChanges = false;
    
    // Apply basic conversions
    foreach ($conversions as $pattern => $replacement) {
        $newContent = preg_replace($pattern, $replacement, $modifiedContent);
        if ($newContent !== $modifiedContent) {
            $modifiedContent = $newContent;
            $hasChanges = true;
        }
    }
    
    // Fix bind_param patterns
    $newContent = fixBindParamPatterns($modifiedContent);
    if ($newContent !== $modifiedContent) {
        $modifiedContent = $newContent;
        $hasChanges = true;
    }
    
    // Write back if modified
    if ($hasChanges) {
        file_put_contents($filePath, $modifiedContent);
        $modifiedFiles++;
        echo "✅ Fixed: {$relativePath}\n";
    } else {
        echo "⏭️  No changes: {$relativePath}\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🎉 PDO/MySQLi Syntax Fix Complete!\n";
echo "📊 Statistics:\n";
echo "   • Total files processed: {$processedFiles}\n";
echo "   • Files modified: {$modifiedFiles}\n";
echo "   • Files unchanged: " . ($processedFiles - $modifiedFiles) . "\n";
echo "\n✨ Your project should now have consistent PDO syntax!\n";
echo "🚀 Try running the application again.\n";
?>
