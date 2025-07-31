<?php
/**
 * Admin Panel Diagnostics
 * Complete diagnostic tool for the admin panel
 */

session_start();

// Include configuration and database
require_once '../config/config.php';
require_once '../includes/database.php';

$diagnostics = [];

// Helper function to add diagnostic result
function addDiagnostic($category, $test, $status, $message, $fix = null) {
    global $diagnostics;
    $diagnostics[$category][] = [
        'test' => $test,
        'status' => $status, // 'pass', 'warning', 'fail'
        'message' => $message,
        'fix' => $fix
    ];
}

// CSS for better display
echo '<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.diagnostic-category { margin-bottom: 30px; }
.test-pass { color: green; font-weight: bold; }
.test-warning { color: orange; font-weight: bold; }
.test-fail { color: red; font-weight: bold; }
.fix-suggestion { background: #f0f0f0; padding: 10px; margin: 5px 0; border-left: 4px solid #ccc; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>';

echo "<h1>Admin Panel Diagnostics</h1>";

// Test 1: File Structure
echo "<h2>File Structure Tests</h2>";
$requiredFiles = [
    'config/config.php' => '../config/config.php',
    'includes/database.php' => '../includes/database.php',
    'classes/Edition.php' => '../classes/Edition.php',
    'classes/Analytics.php' => '../classes/Analytics.php',
    'classes/Category.php' => '../classes/Category.php'
];

foreach ($requiredFiles as $description => $path) {
    if (file_exists($path)) {
        addDiagnostic('files', $description, 'pass', "File exists: {$path}");
    } else {
        addDiagnostic('files', $description, 'fail', "Missing file: {$path}", "Please ensure the file exists or check your installation");
    }
}

// Test 2: Database Connection
echo "<h2>Database Connection Tests</h2>";
try {
    $db = Database::getInstance();
    addDiagnostic('database', 'Database Instance', 'pass', 'Database singleton created successfully');
    
    $pdo = $db->getPDO();
    addDiagnostic('database', 'PDO Connection', 'pass', 'PDO connection established');
    
    $mysqli = $db->getConnection();
    addDiagnostic('database', 'MySQLi Connection', 'pass', 'MySQLi connection established');
    
} catch (Exception $e) {
    addDiagnostic('database', 'Database Connection', 'fail', 'Connection failed: ' . $e->getMessage(), 'Check database credentials in config/config.php and ensure MySQL is running');
}

// Test 3: Database Tables
echo "<h2>Database Tables</h2>";
try {
    $db = Database::getInstance();
    $requiredTables = [
        'editions' => 'Main newspaper editions',
        'categories' => 'Content categories',
        'page_analytics' => 'Analytics tracking',
        'clips' => 'User clips/clippings',
        'admin_users' => 'Admin user accounts'
    ];
    
    foreach ($requiredTables as $table => $description) {
        $stmt = $db->query("SHOW TABLES LIKE ?", [$table]);
        if ($stmt->rowCount() > 0) {
            // Check if table has data
            $countStmt = $db->query("SELECT COUNT(*) as count FROM {$table}");
            $count = $countStmt->fetch()['count'];
            addDiagnostic('tables', $table, 'pass', "{$description} - Table exists with {$count} records");
        } else {
            addDiagnostic('tables', $table, 'warning', "{$description} - Table not found", "Run database.sql to create missing tables");
        }
    }
} catch (Exception $e) {
    addDiagnostic('tables', 'Table Check', 'fail', 'Table check failed: ' . $e->getMessage());
}

// Test 4: Class Functionality
echo "<h2>Class Functionality Tests</h2>";
$classes = [
    'Edition' => ['getTotalCount', 'getPublished'],
    'Analytics' => ['getTotalViews', 'getMonthlyViews'],
    'Category' => ['getAll']
];

foreach ($classes as $className => $methods) {
    $classPath = "../classes/{$className}.php";
    if (file_exists($classPath)) {
        require_once $classPath;
        
        if (class_exists($className)) {
            try {
                $instance = new $className();
                addDiagnostic('classes', "{$className} Instantiation", 'pass', "Class {$className} instantiated successfully");
                
                foreach ($methods as $method) {
                    if (method_exists($instance, $method)) {
                        try {
                            $result = $instance->$method();
                            addDiagnostic('classes', "{$className}::{$method}", 'pass', "Method executed successfully, returned: " . gettype($result));
                        } catch (Exception $e) {
                            addDiagnostic('classes', "{$className}::{$method}", 'warning', "Method exists but failed: " . $e->getMessage());
                        }
                    } else {
                        addDiagnostic('classes', "{$className}::{$method}", 'fail', "Method not found", "Add the {$method} method to the {$className} class");
                    }
                }
                
            } catch (Exception $e) {
                addDiagnostic('classes', "{$className} Instantiation", 'fail', "Failed to instantiate: " . $e->getMessage());
            }
        } else {
            addDiagnostic('classes', $className, 'fail', "Class not found after include");
        }
    }
}

// Test 5: Constants and Configuration
echo "<h2>Configuration Tests</h2>";
$requiredConstants = [
    'ENVIRONMENT', 'DB_HOST', 'DB_NAME', 'DB_USER', 'ITEMS_PER_PAGE', 
    'BASE_PATH', 'UPLOAD_PATH', 'DEBUG_MODE'
];

foreach ($requiredConstants as $constant) {
    if (defined($constant)) {
        $value = constant($constant);
        addDiagnostic('config', $constant, 'pass', "Defined: {$value}");
    } else {
        addDiagnostic('config', $constant, 'fail', "Not defined", "Add {$constant} to config/config.php");
    }
}

// Test 6: Permissions
echo "<h2>Directory Permissions</h2>";
$directories = [
    '../uploads/' => 'Upload directory',
    '../cache/' => 'Cache directory',
    '../temp/' => 'Temporary files'
];

foreach ($directories as $dir => $description) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            addDiagnostic('permissions', $description, 'pass', "Directory {$dir} is writable");
        } else {
            addDiagnostic('permissions', $description, 'warning', "Directory {$dir} is not writable", "Set directory permissions to 755 or 777");
        }
    } else {
        addDiagnostic('permissions', $description, 'fail', "Directory {$dir} does not exist", "Create the directory");
    }
}

// Display Results
foreach ($diagnostics as $category => $tests) {
    echo "<div class='diagnostic-category'>";
    echo "<h3>" . ucfirst($category) . " Tests</h3>";
    echo "<table>";
    echo "<tr><th>Test</th><th>Status</th><th>Message</th><th>Fix</th></tr>";
    
    foreach ($tests as $test) {
        $statusClass = "test-{$test['status']}";
        $statusText = strtoupper($test['status']);
        echo "<tr>";
        echo "<td>{$test['test']}</td>";
        echo "<td class='{$statusClass}'>{$statusText}</td>";
        echo "<td>{$test['message']}</td>";
        echo "<td>" . ($test['fix'] ? "<div class='fix-suggestion'>{$test['fix']}</div>" : '-') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "</div>";
}

echo "<h2>Summary</h2>";
$totalTests = 0;
$passedTests = 0;
$warningTests = 0;
$failedTests = 0;

foreach ($diagnostics as $category => $tests) {
    foreach ($tests as $test) {
        $totalTests++;
        switch ($test['status']) {
            case 'pass': $passedTests++; break;
            case 'warning': $warningTests++; break;
            case 'fail': $failedTests++; break;
        }
    }
}

echo "<p><strong>Total Tests:</strong> {$totalTests}</p>";
echo "<p><strong class='test-pass'>Passed:</strong> {$passedTests}</p>";
echo "<p><strong class='test-warning'>Warnings:</strong> {$warningTests}</p>";
echo "<p><strong class='test-fail'>Failed:</strong> {$failedTests}</p>";

if ($failedTests === 0 && $warningTests === 0) {
    echo "<p style='color: green; font-size: 18px; font-weight: bold;'>🎉 All tests passed! Your admin panel should be working correctly.</p>";
} elseif ($failedTests === 0) {
    echo "<p style='color: orange; font-size: 18px; font-weight: bold;'>⚠️ Some warnings found. Admin panel should work but may have minor issues.</p>";
} else {
    echo "<p style='color: red; font-size: 18px; font-weight: bold;'>❌ Critical issues found. Please fix the failed tests before using the admin panel.</p>";
}

echo "<p><a href='dashboard.php'>Back to Dashboard</a> | <a href='test-database.php'>Database Test</a></p>";
?>
