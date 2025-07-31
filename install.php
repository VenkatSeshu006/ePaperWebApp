<?php
/**
 * E-Paper CMS v2.0 - Installation Script
 * Automated setup for new installations
 */

echo "🚀 E-Paper CMS v2.0 - Installation Script\n";
echo "=========================================\n\n";

// Check PHP version
$phpVersion = PHP_VERSION;
echo "📋 System Requirements Check:\n";
echo "   PHP Version: $phpVersion ";
if (version_compare($phpVersion, '7.4.0', '>=')) {
    echo "✅\n";
} else {
    echo "❌ (Requires PHP 7.4+)\n";
    exit(1);
}

// Check required extensions
$requiredExtensions = ['mysqli', 'pdo', 'gd', 'json', 'mbstring'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    echo "   Extension $ext: ";
    if (extension_loaded($ext)) {
        echo "✅\n";
    } else {
        echo "❌\n";
        $missingExtensions[] = $ext;
    }
}

if (!empty($missingExtensions)) {
    echo "\n❌ Missing required extensions: " . implode(', ', $missingExtensions) . "\n";
    echo "Please install the missing extensions and try again.\n";
    exit(1);
}

echo "\n📁 Directory Structure Check:\n";

// Create necessary directories
$directories = [
    'uploads/',
    'uploads/temp/',
    'cache/',
    'uploads/clips/',
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "   Created directory: $dir ✅\n";
        } else {
            echo "   Failed to create directory: $dir ❌\n";
        }
    } else {
        echo "   Directory exists: $dir ✅\n";
    }
    
    // Check if writable
    if (is_writable($dir)) {
        echo "   Directory writable: $dir ✅\n";
    } else {
        echo "   Directory not writable: $dir ⚠️\n";
        echo "     Please set permissions: chmod 755 $dir\n";
    }
}

echo "\n🗄️ Database Setup:\n";

// Get database credentials
echo "Please enter your database credentials:\n";
echo "Database Host (default: localhost): ";
$host = trim(fgets(STDIN)) ?: 'localhost';

echo "Database Name (default: epaper_cms): ";
$dbname = trim(fgets(STDIN)) ?: 'epaper_cms';

echo "Database Username: ";
$username = trim(fgets(STDIN));

echo "Database Password: ";
$password = trim(fgets(STDIN));

// Test database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "   Database connection: ✅\n";
} catch (PDOException $e) {
    echo "   Database connection failed: ❌\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "\nWould you like to create the database? (y/n): ";
    $createDb = trim(fgets(STDIN));
    
    if (strtolower($createDb) === 'y') {
        try {
            $pdo = new PDO("mysql:host=$host", $username, $password);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
            $pdo->exec("USE `$dbname`");
            echo "   Database created: ✅\n";
        } catch (PDOException $e) {
            echo "   Database creation failed: ❌\n";
            echo "   Error: " . $e->getMessage() . "\n";
            exit(1);
        }
    } else {
        exit(1);
    }
}

// Update database configuration
$configContent = "<?php
/**
 * Optimized Database Connection
 * Clean and efficient database connection for E-Paper CMS v2.0
 */

// Database configuration
\$host = \"$host\";
\$user = \"$username\";
\$pass = \"$password\";
\$db = \"$dbname\";

// Global connection variable
\$conn = null;

/**
 * Get database connection
 * @return mysqli|null
 */
function getConnection() {
    global \$conn, \$host, \$user, \$pass, \$db;
    
    if (\$conn === null) {
        try {
            \$conn = new mysqli(\$host, \$user, \$pass, \$db);
            
            if (\$conn->connect_error) {
                error_log(\"Database connection failed: \" . \$conn->connect_error);
                return null;
            }
            
            \$conn->set_charset(\"utf8mb4\");
            
        } catch (Exception \$e) {
            error_log(\"Database connection error: \" . \$e->getMessage());
            return null;
        }
    }
    
    return \$conn;
}

/**
 * Check if database is connected
 * @return bool
 */
function isDatabaseConnected() {
    \$connection = getConnection();
    return \$connection !== null && !\$connection->connect_error;
}

/**
 * Close database connection
 */
function closeDatabaseConnection() {
    global \$conn;
    if (\$conn) {
        \$conn->close();
        \$conn = null;
    }
}
?>";

if (file_put_contents('includes/database.php', $configContent)) {
    echo "   Database configuration updated: ✅\n";
} else {
    echo "   Failed to update database configuration: ❌\n";
}

// Check for SQL file and import
echo "\n📤 Database Import:\n";
$sqlFiles = ['epaper_enhanced_safe.sql', 'epaper_enhanced.sql', 'epaper.sql'];
$sqlFile = null;

foreach ($sqlFiles as $file) {
    if (file_exists($file)) {
        $sqlFile = $file;
        break;
    }
}

if ($sqlFile) {
    echo "   Found SQL file: $sqlFile\n";
    echo "   Would you like to import it? (y/n): ";
    $import = trim(fgets(STDIN));
    
    if (strtolower($import) === 'y') {
        $sql = file_get_contents($sqlFile);
        if ($sql) {
            try {
                $pdo->exec($sql);
                echo "   Database imported successfully: ✅\n";
            } catch (PDOException $e) {
                echo "   Database import failed: ❌\n";
                echo "   Error: " . $e->getMessage() . "\n";
            }
        }
    }
} else {
    echo "   No SQL file found for import ⚠️\n";
    echo "   Please import your database manually\n";
}

// Create .htaccess for security
echo "\n🔐 Security Setup:\n";
$htaccessContent = "# E-Paper CMS Security Rules
RewriteEngine On

# Protect sensitive files
<Files ~ \"\\.(sql|log|env)$\">
    Order allow,deny
    Deny from all
</Files>

# Protect includes directory
<Files ~ \"^(database|config)\\.php$\">
    Order allow,deny
    Deny from all
</Files>

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/x-javascript
</IfModule>

# Browser caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg \"access plus 1 month\"
    ExpiresByType image/jpeg \"access plus 1 month\"
    ExpiresByType image/gif \"access plus 1 month\"
    ExpiresByType image/png \"access plus 1 month\"
    ExpiresByType text/css \"access plus 1 month\"
    ExpiresByType application/pdf \"access plus 1 month\"
    ExpiresByType text/javascript \"access plus 1 month\"
    ExpiresByType application/javascript \"access plus 1 month\"
</IfModule>
";

if (file_put_contents('.htaccess', $htaccessContent)) {
    echo "   Security .htaccess created: ✅\n";
} else {
    echo "   Failed to create .htaccess: ⚠️\n";
}

// Final checks
echo "\n🔍 Final Checks:\n";

// Test homepage
if (file_exists('index.php')) {
    echo "   Homepage file exists: ✅\n";
} else {
    echo "   Homepage file missing: ❌\n";
}

// Test admin panel
if (file_exists('admin/dashboard.php')) {
    echo "   Admin panel exists: ✅\n";
} else {
    echo "   Admin panel missing: ❌\n";
}

// Test database connection
require_once 'includes/database.php';
$testConn = getConnection();
if ($testConn) {
    echo "   Database connection test: ✅\n";
    $testConn->close();
} else {
    echo "   Database connection test: ❌\n";
}

echo "\n✅ Installation completed!\n";
echo "\n📋 Next Steps:\n";
echo "1. Visit your website to test the homepage\n";
echo "2. Go to /admin/ to access the admin panel\n";
echo "3. Upload your first edition\n";
echo "4. Customize site settings\n";
echo "5. Set up regular backups\n";

echo "\n🔗 Important URLs:\n";
echo "   Homepage: http://your-domain/epaper-site/\n";
echo "   Admin Panel: http://your-domain/epaper-site/admin/\n";

echo "\n🎉 E-Paper CMS v2.0 is ready to use!\n";
echo "Thank you for choosing E-Paper CMS!\n";
?>
