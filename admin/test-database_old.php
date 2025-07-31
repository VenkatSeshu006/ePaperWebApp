<?php
/**
 * Admin Database Test
 * Test database connectivity and basic functionality
 */

session_start();

// Include configuration and database
require_once '../config/config.php';
require_once '../includes/database.php';

echo "<h1>Admin Panel Database Test</h1>";

// Test 1: Basic Configuration
echo "<h2>1. Configuration Test</h2>";
echo "<p>Environment: " . ENVIRONMENT . "</p>";
echo "<p>Database Host: " . DB_HOST . "</p>";
echo "<p>Database Name: " . DB_NAME . "</p>";
echo "<p>Debug Mode: " . (DEBUG_MODE ? 'Enabled' : 'Disabled') . "</p>";

// Test 2: Database Connection
echo "<h2>2. Database Connection Test</h2>";
try {
    $db = Database::getInstance();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Test PDO connection
    $pdo = $db->getPDO();
    echo "<p style='color: green;'>✓ PDO connection successful</p>";
    
    // Test MySQLi connection
    $mysqli = $db->getConnection();
    echo "<p style='color: green;'>✓ MySQLi connection successful</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test 3: Table Existence
echo "<h2>3. Database Tables Test</h2>";
try {
    $db = Database::getInstance();
    $requiredTables = ['editions', 'categories', 'clips', 'analytics'];
    
    foreach ($requiredTables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE ?", [$table]);
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ Table '{$table}' exists</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Table '{$table}' not found</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Table check failed: " . $e->getMessage() . "</p>";
}

// Test 4: Class Loading
echo "<h2>4. Class Loading Test</h2>";
$classFiles = ['Edition', 'Category', 'Analytics'];
foreach ($classFiles as $class) {
    $classPath = "../classes/{$class}.php";
    if (file_exists($classPath)) {
        require_once $classPath;
        if (class_exists($class)) {
            echo "<p style='color: green;'>✓ Class '{$class}' loaded successfully</p>";
            
            // Test class instantiation
            try {
                $instance = new $class();
                echo "<p style='color: green;'>✓ Class '{$class}' instantiated successfully</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>✗ Class '{$class}' instantiation failed: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Class '{$class}' not found after include</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Class file '{$classPath}' not found</p>";
    }
}

echo "<h2>Test Complete</h2>";
echo "<p><a href='dashboard.php'>Back to Dashboard</a></p>";
?>
