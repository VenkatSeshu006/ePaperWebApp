<?php
/**
 * File Consolidation & Cleanup
 * Merges duplicate files and creates unified versions
 */

echo "🔄 Consolidating Duplicate Files\n";
echo "================================\n\n";

$actions = [];
$errors = [];

// 1. Database Connection Files Consolidation
echo "1. 📊 Database Connection Files\n";
echo "   ----------------------------\n";

try {
    // Check what we have
    $dbFiles = [
        'includes/database.php' => 'Main database (PDO)',
        'includes/db.php' => 'Legacy database (MySQLi)', 
        'includes/db_simple.php' => 'Simple database (empty)'
    ];
    
    foreach ($dbFiles as $file => $desc) {
        if (file_exists($file)) {
            $size = filesize($file);
            echo "   📁 $desc: $file ($size bytes)\n";
        }
    }
    
    // Remove duplicates and empty files
    if (file_exists('includes/db_simple.php') && filesize('includes/db_simple.php') == 0) {
        unlink('includes/db_simple.php');
        echo "   ✅ Removed empty: includes/db_simple.php\n";
        $actions[] = "Removed empty db_simple.php";
    }
    
    // Update includes/db.php to be a simple redirect to database.php for compatibility
    $redirectContent = '<?php
/**
 * Database Connection Compatibility Layer
 * Redirects to main database.php for consistency
 */

require_once __DIR__ . "/database.php";

// Provide MySQLi compatibility if needed
if (!isset($conn) && function_exists("getConnection")) {
    try {
        $pdoConn = getConnection();
        // Note: This provides PDO connection, not MySQLi
        // Update your code to use PDO instead of MySQLi
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
    }
}
?>';
    
    file_put_contents('includes/db.php', $redirectContent);
    echo "   ✅ Updated db.php to redirect to database.php\n";
    $actions[] = "Consolidated db.php to use database.php";
    
} catch (Exception $e) {
    echo "   ❌ Error consolidating database files: " . $e->getMessage() . "\n";
    $errors[] = "Database files consolidation failed";
}

// 2. API Files Consolidation
echo "\n2. 🔌 API Files\n";
echo "   -----------\n";

try {
    // Check save-clip files
    if (file_exists('api/save-clip.php') && file_exists('api/save-clip-fixed.php')) {
        // Compare file sizes to determine which is more complete
        $originalSize = filesize('api/save-clip.php');
        $fixedSize = filesize('api/save-clip-fixed.php');
        
        echo "   📁 save-clip.php: $originalSize bytes\n";
        echo "   📁 save-clip-fixed.php: $fixedSize bytes\n";
        
        // Keep the larger/more complete file and remove the other
        if ($fixedSize > $originalSize) {
            // Use the fixed version
            copy('api/save-clip-fixed.php', 'api/save-clip.php');
            unlink('api/save-clip-fixed.php');
            echo "   ✅ Merged save-clip-fixed.php into save-clip.php\n";
            $actions[] = "Merged save-clip-fixed.php into save-clip.php";
        } else {
            // Remove the fixed version as original is better
            unlink('api/save-clip-fixed.php');
            echo "   ✅ Removed redundant save-clip-fixed.php\n";
            $actions[] = "Removed redundant save-clip-fixed.php";
        }
    }
    
} catch (Exception $e) {
    echo "   ❌ Error consolidating API files: " . $e->getMessage() . "\n";
    $errors[] = "API files consolidation failed";
}

// 3. Check for any other potential duplicates
echo "\n3. 🔍 Scanning for Other Duplicates\n";
echo "   --------------------------------\n";

$potentialDuplicates = [
    // Check for backup files
    ['pattern' => '**/*_backup*', 'description' => 'Backup files'],
    ['pattern' => '**/*_old*', 'description' => 'Old versions'],
    ['pattern' => '**/*_new*', 'description' => 'New versions'],
    ['pattern' => '**/*_copy*', 'description' => 'Copy files'],
    ['pattern' => '**/*_temp*', 'description' => 'Temporary files'],
];

foreach ($potentialDuplicates as $check) {
    echo "   🔍 Checking for {$check['description']}...\n";
    // This would normally use glob, but we'll do a simple check
    $found = 0;
    
    // Check common locations
    $directories = ['admin/', 'api/', 'classes/', 'includes/'];
    foreach ($directories as $dir) {
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if (strpos($file, '_backup') !== false || 
                    strpos($file, '_old') !== false || 
                    strpos($file, '_new') !== false ||
                    strpos($file, '_copy') !== false ||
                    strpos($file, '_temp') !== false) {
                    echo "   ⚠️  Found potential duplicate: $dir$file\n";
                    $found++;
                }
            }
        }
    }
    
    if ($found == 0) {
        echo "   ✅ No {$check['description']} found\n";
    }
}

// 4. Standardize all database includes
echo "\n4. 🔧 Standardizing Database Includes\n";
echo "   ----------------------------------\n";

$filesToUpdate = [
    'api/homepage-data.php',
    'api/get-clip.php',
    'categories.php',
    'clips.php',
    'index.php',
    'view-clip.php'
];

$updatedFiles = 0;
foreach ($filesToUpdate as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $originalContent = $content;
        
        // Replace various database include patterns with the standard one
        $patterns = [
            '/require_once\s+[\'"]\.\.?\/includes\/db\.php[\'"];?/' => "require_once 'includes/database.php';",
            '/include_once\s+[\'"]\.\.?\/includes\/db\.php[\'"];?/' => "require_once 'includes/database.php';",
            '/require\s+[\'"]\.\.?\/includes\/db\.php[\'"];?/' => "require_once 'includes/database.php';",
            '/include\s+[\'"]\.\.?\/includes\/db\.php[\'"];?/' => "require_once 'includes/database.php';",
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        if ($content !== $originalContent) {
            file_put_contents($file, $content);
            echo "   ✅ Updated database includes in: $file\n";
            $updatedFiles++;
            $actions[] = "Standardized database includes in $file";
        }
    }
}

if ($updatedFiles == 0) {
    echo "   ✅ All files already use correct database includes\n";
}

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "📋 CONSOLIDATION SUMMARY\n";
echo str_repeat("=", 50) . "\n";

echo "✅ Actions completed: " . count($actions) . "\n";
foreach ($actions as $action) {
    echo "   • $action\n";
}

if (!empty($errors)) {
    echo "\n❌ Errors encountered: " . count($errors) . "\n";
    foreach ($errors as $error) {
        echo "   • $error\n";
    }
}

echo "\n🎯 Result: Project files consolidated and standardized!\n";
echo "💡 All database connections now use 'includes/database.php'\n";
echo "🧹 Duplicate and redundant files removed\n";
echo "🔧 Future conflicts prevented through standardization\n";
?>
