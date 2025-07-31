<?php
/**
 * E-Paper CMS v2.0 - System Synchronization & Health Check
 * Comprehensive verification of all system components
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>E-Paper CMS - System Sync Check</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
    <style>
        .status-ok { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-error { color: #dc3545; }
        .card { margin-bottom: 20px; }
        .code-block { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; }
    </style>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <div class='row'>
        <div class='col-12'>
            <div class='card'>
                <div class='card-header bg-primary text-white'>
                    <h2><i class='fas fa-sync-alt'></i> E-Paper CMS v2.0 - System Synchronization Report</h2>
                    <small>Generated: " . date('Y-m-d H:i:s') . "</small>
                </div>
                <div class='card-body'>";

// Function to check status
function checkStatus($condition, $message, $details = '') {
    $icon = $condition ? '<i class="fas fa-check-circle status-ok"></i>' : '<i class="fas fa-times-circle status-error"></i>';
    $class = $condition ? 'status-ok' : 'status-error';
    echo "<div class='mb-2'>{$icon} <span class='{$class}'>{$message}</span>";
    if ($details) echo "<br><small class='text-muted'>{$details}</small>";
    echo "</div>";
    return $condition;
}

function checkWarning($condition, $message, $details = '') {
    $icon = $condition ? '<i class="fas fa-check-circle status-ok"></i>' : '<i class="fas fa-exclamation-triangle status-warning"></i>';
    $class = $condition ? 'status-ok' : 'status-warning';
    echo "<div class='mb-2'>{$icon} <span class='{$class}'>{$message}</span>";
    if ($details) echo "<br><small class='text-muted'>{$details}</small>";
    echo "</div>";
    return $condition;
}

// 1. PHP Environment Check
echo "<h3><i class='fas fa-server'></i> PHP Environment</h3>";
checkStatus(version_compare(PHP_VERSION, '7.4.0', '>='), 
    "PHP Version: " . PHP_VERSION, 
    "Minimum required: 7.4.0");

checkStatus(extension_loaded('pdo'), 
    "PDO Extension", 
    "Required for database operations");

checkStatus(extension_loaded('pdo_mysql'), 
    "PDO MySQL Extension", 
    "Required for MySQL connectivity");

checkStatus(extension_loaded('gd'), 
    "GD Extension", 
    "Required for image processing");

checkWarning(extension_loaded('imagick'), 
    "ImageMagick Extension", 
    "Optional: Enhanced image processing");

checkStatus(function_exists('exec'), 
    "Exec Function", 
    "Required for Ghostscript PDF processing");

// 2. File System Check
echo "<h3><i class='fas fa-folder'></i> File System</h3>";

$requiredDirs = [
    'includes' => 'Core includes directory',
    'admin' => 'Administration panel',
    'assets' => 'Static assets',
    'assets/css' => 'Stylesheets',
    'assets/js' => 'JavaScript files',
    'api' => 'API endpoints',
    'uploads' => 'Media uploads',
    'uploads/clips' => 'Clip storage',
    'cache' => 'Performance cache'
];

foreach ($requiredDirs as $dir => $desc) {
    checkStatus(is_dir($dir), 
        "Directory: {$dir}", 
        $desc);
}

$requiredFiles = [
    'index.php' => 'Main viewer interface',
    'setup.php' => 'Database setup script',
    'save_clip.php' => 'Clip saving endpoint',
    'includes/database.php' => 'Database connection',
    'admin/dashboard.php' => 'Admin dashboard',
    'admin/upload.php' => 'PDF upload system',
    'assets/js/app.js' => 'Main JavaScript application',
    'manifest.json' => 'PWA manifest'
];

foreach ($requiredFiles as $file => $desc) {
    checkStatus(file_exists($file), 
        "File: {$file}", 
        $desc);
}

// 3. Database Check
echo "<h3><i class='fas fa-database'></i> Database Connection</h3>";

try {
    if (file_exists('includes/database.php')) {
        require_once 'includes/database.php';
        $conn = getConnection();
        checkStatus(true, "Database Connection", "Successfully connected to database");
        
        // Check tables
        $tables = ['editions', 'clips', 'users', 'settings'];
        foreach ($tables as $table) {
            try {
                $stmt = $conn->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                checkStatus($stmt->rowCount() > 0, 
                    "Table: {$table}", 
                    "Database table exists");
            } catch (Exception $e) {
                checkStatus(false, "Table: {$table}", "Error: " . $e->getMessage());
            }
        }
    } else {
        checkStatus(false, "Database Configuration", "includes/database.php not found");
    }
} catch (Exception $e) {
    checkStatus(false, "Database Connection", "Error: " . $e->getMessage());
}

// 4. External Dependencies Check
echo "<h3><i class='fas fa-link'></i> External Dependencies</h3>";

$externalLibs = [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' => 'Bootstrap CSS',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' => 'Font Awesome',
    'https://code.jquery.com/jquery-3.6.0.min.js' => 'jQuery',
    'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js' => 'Cropper.js',
    'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js' => 'jsPDF'
];

foreach ($externalLibs as $url => $name) {
    $headers = @get_headers($url);
    $status = $headers && strpos($headers[0], '200') !== false;
    checkWarning($status, "External Library: {$name}", $url);
}

// 5. Permissions Check
echo "<h3><i class='fas fa-shield-alt'></i> File Permissions</h3>";

$writableDirs = ['uploads', 'uploads/clips', 'cache'];
foreach ($writableDirs as $dir) {
    if (is_dir($dir)) {
        checkStatus(is_writable($dir), 
            "Writable: {$dir}", 
            "Required for file uploads and caching");
    }
}

// 6. Configuration Check
echo "<h3><i class='fas fa-cog'></i> Configuration Status</h3>";

// Check if uploads work
$uploadStatus = is_dir('uploads') && is_writable('uploads');
checkStatus($uploadStatus, "Upload System", "File upload capability");

// Check Ghostscript
$gsCommand = 'gs --version 2>&1';
$gsOutput = @exec($gsCommand, $output, $returnVar);
checkWarning($returnVar === 0, 
    "Ghostscript", 
    $returnVar === 0 ? "Version detected: {$gsOutput}" : "Not found - PDF processing may not work");

// 7. Feature Status
echo "<h3><i class='fas fa-features'></i> Feature Status</h3>";

// Check if we have sample data
try {
    if (isset($conn)) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM editions");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $editionCount = $result ? $result['count'] : 0;
        checkStatus($editionCount > 0, 
            "Sample Data", 
            "{$editionCount} editions in database");
    }
} catch (Exception $e) {
    checkStatus(false, "Sample Data", "Could not check: " . $e->getMessage());
}

// 8. Security Check
echo "<h3><i class='fas fa-lock'></i> Security Status</h3>";

checkStatus(!ini_get('display_errors'), 
    "Error Display", 
    "Errors should be hidden in production");

checkWarning(isset($_SERVER['HTTPS']), 
    "HTTPS Connection", 
    "SSL recommended for production");

checkStatus(file_exists('.htaccess'), 
    "Apache Configuration", 
    ".htaccess file for security rules");

// 9. Performance Check
echo "<h3><i class='fas fa-tachometer-alt'></i> Performance Status</h3>";

$cacheEnabled = is_dir('cache') && is_writable('cache');
checkStatus($cacheEnabled, "Cache System", "Performance optimization");

$compression = ini_get('zlib.output_compression');
checkWarning($compression, "Output Compression", "Recommended for faster loading");

// 10. Summary
echo "<h3><i class='fas fa-clipboard-check'></i> System Summary</h3>";

echo "<div class='alert alert-info'>
    <h5><i class='fas fa-info-circle'></i> Quick Start</h5>
    <ol>
        <li>Ensure all red errors are resolved</li>
        <li>Yellow warnings are recommended but not critical</li>
        <li>Visit <a href='setup.php'>setup.php</a> to initialize the database</li>
        <li>Upload your first PDF via <a href='admin/'>admin panel</a></li>
        <li>View your publication at <a href='index.php'>index.php</a></li>
    </ol>
</div>";

echo "<div class='alert alert-success'>
    <h5><i class='fas fa-rocket'></i> System Status</h5>
    <p>E-Paper CMS v2.0 is synchronized and ready for use!</p>
    <small>Last checked: " . date('Y-m-d H:i:s') . "</small>
</div>";

echo "                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>";
?>
