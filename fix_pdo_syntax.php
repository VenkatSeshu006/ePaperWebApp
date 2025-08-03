<?php
/**
 * PDO Syntax Fix Script
 * Converts MySQLi syntax to PDO syntax across admin files
 */

// Files to fix
$adminFiles = [
    'admin/clips.php',
    'admin/categories.php',
    'admin/users.php',
    'admin/page_settings.php',
    'admin/setup-database.php',
    'admin/test-database.php',
    'admin/manage_editions.php',
    'admin/upload.php',
    'admin/settings.php'
];

echo "Starting PDO syntax fix...\n";

foreach ($adminFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    
    if (!file_exists($fullPath)) {
        echo "Skipping $file (not found)\n";
        continue;
    }
    
    echo "Processing $file...\n";
    
    $content = file_get_contents($fullPath);
    $originalContent = $content;
    
    // Fix fetch_assoc() -> fetch()
    $content = preg_replace('/->fetch_assoc\(\)/', '->fetch()', $content);
    
    // Fix bind_param and get_result patterns
    // This is more complex as we need to convert the entire prepared statement structure
    
    // Pattern 1: $stmt->execute([$var]); $result = $stmt->get_result(); $row = $result->fetch();
    $content = preg_replace_callback(
        '/(\$\w+)\s*->\s*bind_param\s*\(\s*["\']([^"\']+)["\']\s*,\s*([^)]+)\)\s*;\s*\1\s*->\s*execute\s*\(\s*\)\s*;\s*(\$\w+)\s*=\s*\1\s*->\s*get_result\s*\(\s*\)\s*;/',
        function($matches) {
            $stmt = $matches[1];
            $types = $matches[2];
            $params = $matches[3];
            $result = $matches[4];
            
            // Convert parameter types to array format
            $paramArray = explode(',', $params);
            $paramArray = array_map('trim', $paramArray);
            $paramList = '[' . implode(', ', $paramArray) . ']';
            
            return "{$stmt}->execute({$paramList}); {$result} = {$stmt};";
        },
        $content
    );
    
    // Pattern 2: Simple ->close() removal
    $content = preg_replace('/\$\w+\s*->\s*close\s*\(\s*\)\s*;/', '// Connection closed automatically', $content);
    
    if ($content !== $originalContent) {
        // Backup original file
        $backupPath = $fullPath . '.backup.' . date('Y-m-d-H-i-s');
        copy($fullPath, $backupPath);
        
        // Write fixed content
        file_put_contents($fullPath, $content);
        echo "  ✓ Fixed $file (backup: " . basename($backupPath) . ")\n";
    } else {
        echo "  - No changes needed for $file\n";
    }
}

echo "\nPDO syntax fix completed!\n";
?>
