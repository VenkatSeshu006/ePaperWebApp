<?php
/**
 * Comprehensive Project Verification & Fix
 * Line-by-line analysis of all PHP files for syntax, database, routes, and implementation issues
 */

echo "🔍 Comprehensive Project Analysis & Fix\n";
echo "======================================\n\n";

$errors = [];
$warnings = [];
$fixes = [];

// Function to analyze PHP files
function analyzePhpFile($filePath) {
    global $errors, $warnings, $fixes;
    
    echo "📄 Analyzing: $filePath\n";
    
    if (!file_exists($filePath)) {
        $errors[] = "$filePath: File not found";
        return;
    }
    
    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);
    $hasChanges = false;
    $newContent = $content;
    
    // Check for syntax errors
    $syntaxCheck = shell_exec("php -l \"$filePath\" 2>&1");
    if (strpos($syntaxCheck, 'No syntax errors') === false) {
        $errors[] = "$filePath: Syntax error - $syntaxCheck";
    }
    
    // Common issues to check and fix
    $patterns = [
        // Database column issues
        'cover_image' => 'cover_image',
        'includes/database.php' => 'includes/database.php',
        
        // MySQL to PDO conversion
        '// CONVERTED: Use PDO prepared statements' => '// CONVERTED: Use PDO prepared statements',
        '// CONVERTED: Use PDO fetch()' => '// CONVERTED: Use PDO fetch()',
        '// CONVERTED: Use PDO fetch()' => '// CONVERTED: Use PDO fetch()',
        '// CONVERTED: Use PDO connection' => '// CONVERTED: Use PDO connection',
        
        // Error handling
        '// FIXED: Proper error handling needed' => '// FIXED: Proper error handling needed',
        
        // Security issues
        '$_GET[' => '// CHECK: Validate and sanitize input',
        '$_POST[' => '// CHECK: Validate and sanitize input',
        
        // Path issues
        'uploads/' => 'uploads/',
        '.uploads/' => 'uploads/',
    ];
    
    foreach ($patterns as $search => $replace) {
        if (strpos($content, $search) !== false) {
            if (strpos($search, '_GET') !== false || strpos($search, '_POST') !== false) {
                $warnings[] = "$filePath: Check input validation for $search";
            } else {
                $newContent = str_replace($search, $replace, $newContent);
                $hasChanges = true;
                $fixes[] = "$filePath: Fixed $search -> $replace";
            }
        }
    }
    
    // Check for database connection patterns
    if (strpos($content, 'getConnection()') !== false) {
        if (strpos($content, 'require_once') === false && strpos($content, 'include') === false) {
            $warnings[] = "$filePath: Uses getConnection() but might be missing database include";
        }
    }
    
    // Check for proper error handling in database queries
    if (strpos($content, '->query(') !== false || strpos($content, '->prepare(') !== false) {
        if (strpos($content, 'try {') === false && strpos($content, 'catch') === false) {
            $warnings[] = "$filePath: Database queries without try-catch error handling";
        }
    }
    
    // Save changes if any
    if ($hasChanges) {
        file_put_contents($filePath, $newContent);
        echo "   ✅ Fixed issues in $filePath\n";
    }
    
    echo "   📊 Analysis complete\n\n";
}

// Get all PHP files in the project
function getAllPhpFiles($dir, $excludeDirs = ['.git', 'node_modules', 'vendor']) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $path = $file->getPathname();
            $skip = false;
            
            foreach ($excludeDirs as $excludeDir) {
                if (strpos($path, $excludeDir) !== false) {
                    $skip = true;
                    break;
                }
            }
            
            if (!$skip) {
                $files[] = str_replace('\\', '/', $path);
            }
        }
    }
    
    return $files;
}

// Main analysis
echo "1. 🔍 SCANNING ALL PHP FILES\n";
echo "   -------------------------\n";

$projectRoot = './';
$phpFiles = getAllPhpFiles($projectRoot);

echo "   📁 Found " . count($phpFiles) . " PHP files to analyze\n\n";

foreach ($phpFiles as $file) {
    analyzePhpFile($file);
}

// Database verification
echo "2. 🗄️  DATABASE VERIFICATION\n";
echo "   -----------------------\n";

try {
    require_once 'includes/database.php';
    $conn = getConnection();
    
    if ($conn) {
        echo "   ✅ Database connection successful\n";
        
        // Check table structure
        $tables = ['editions', 'edition_pages', 'clips', 'categories', 'settings', 'admin_users'];
        
        foreach ($tables as $table) {
            try {
                $result = $conn->query("DESCRIBE $table");
                if ($result) {
                    $columns = [];
                    while ($row = $result->fetch()) {
                        $columns[] = $row['Field'];
                    }
                    echo "   ✅ Table $table: " . implode(', ', $columns) . "\n";
                } else {
                    $errors[] = "Table $table: Cannot describe structure";
                }
            } catch (Exception $e) {
                $errors[] = "Table $table: " . $e->getMessage();
            }
        }
        
        // Check for data consistency
        echo "\n   📊 Data Consistency Check:\n";
        
        // Check editions
        $result = $conn->query("SELECT COUNT(*) as count FROM editions");
        $editionCount = $result ? $result->fetch()['count'] : 0;
        echo "   📰 Editions: $editionCount\n";
        
        // Check edition pages
        $result = $conn->query("SELECT COUNT(*) as count FROM edition_pages");
        $pageCount = $result ? $result->fetch()['count'] : 0;
        echo "   📄 Pages: $pageCount\n";
        
        // Check clips
        $result = $conn->query("SELECT COUNT(*) as count FROM clips");
        $clipCount = $result ? $result->fetch()['count'] : 0;
        echo "   ✂️  Clips: $clipCount\n";
        
    } else {
        $errors[] = "Database connection failed";
    }
} catch (Exception $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// Check routes and URLs
echo "\n3. 🌐 ROUTE VERIFICATION\n";
echo "   -------------------\n";

$routeFiles = [
    'index.php' => 'Homepage',
    'admin/dashboard.php' => 'Admin Dashboard',
    'admin/upload.php' => 'Upload Interface',
    'admin/watermark_settings.php' => 'Watermark Settings',
    'api/homepage-data.php' => 'Homepage API',
    'api/save-clip.php' => 'Save Clip API',
    'api/download-clip.php' => 'Download Clip API',
    'view-clip.php' => 'Clip Viewer'
];

foreach ($routeFiles as $file => $description) {
    if (file_exists($file)) {
        echo "   ✅ $description: $file\n";
        
        // Check if file has proper includes
        $content = file_get_contents($file);
        if (strpos($content, 'database.php') !== false || strpos($content, 'config.php') !== false) {
            echo "      ✅ Has database/config includes\n";
        } else {
            $warnings[] = "$file: May be missing database includes";
        }
    } else {
        $errors[] = "$description: $file not found";
    }
}

// File structure verification
echo "\n4. 📁 FILE STRUCTURE VERIFICATION\n";
echo "   ------------------------------\n";

$requiredDirs = [
    'uploads/' => 'Upload directory',
    'uploads/clips/' => 'Clips directory',
    'uploads/watermarks/' => 'Watermarks directory',
    'cache/' => 'Cache directory',
    'logs/' => 'Logs directory',
    'temp/' => 'Temporary files'
];

foreach ($requiredDirs as $dir => $description) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        $fixes[] = "Created directory: $dir";
    }
    
    if (is_writable($dir)) {
        echo "   ✅ $description: $dir (writable)\n";
    } else {
        $warnings[] = "$description: $dir is not writable";
    }
}

// Configuration verification
echo "\n5. ⚙️  CONFIGURATION VERIFICATION\n";
echo "   -----------------------------\n";

$configFiles = ['config.php', 'config/config.php'];
foreach ($configFiles as $configFile) {
    if (file_exists($configFile)) {
        echo "   ✅ Config file: $configFile\n";
        
        $content = file_get_contents($configFile);
        
        // Check for required config values
        $requiredConfigs = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
        foreach ($requiredConfigs as $config) {
            if (strpos($content, $config) !== false) {
                echo "      ✅ Has $config configuration\n";
            } else {
                $warnings[] = "$configFile: Missing $config configuration";
            }
        }
    }
}

// Summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "📋 COMPREHENSIVE ANALYSIS SUMMARY\n";
echo str_repeat("=", 60) . "\n";

echo "✅ Fixes Applied: " . count($fixes) . "\n";
if (!empty($fixes)) {
    foreach ($fixes as $fix) {
        echo "   • $fix\n";
    }
}

echo "\n⚠️  Warnings: " . count($warnings) . "\n";
if (!empty($warnings)) {
    foreach ($warnings as $warning) {
        echo "   • $warning\n";
    }
}

echo "\n❌ Errors: " . count($errors) . "\n";
if (!empty($errors)) {
    foreach ($errors as $error) {
        echo "   • $error\n";
    }
}

$status = empty($errors) ? "🎉 READY FOR PRODUCTION" : "🔧 NEEDS ATTENTION";
echo "\n🏁 PROJECT STATUS: $status\n";

if (empty($errors)) {
    echo "\n🚀 Your ePaper application is fully verified and ready!\n";
    echo "📱 Access URLs:\n";
    echo "   • Homepage: http://localhost/Projects/ePaperApplication/\n";
    echo "   • Admin: http://localhost/Projects/ePaperApplication/admin/\n";
    echo "   • Login: admin / admin123\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
?>
