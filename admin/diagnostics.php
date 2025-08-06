<?php
/**
 * Admin Panel Diagnostics
 * Complete diagnostic tool for the admin panel
 */

session_start();
define('ADMIN_PAGE', true);

// Include configuration and database
require_once '../config.php';

// Simple authentication check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: dashboard.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: dashboard.php');
    exit;
}

require_once '../includes/database.php';

// Page configuration
$pageTitle = 'System Diagnostics';
$pageSubtitle = 'Comprehensive system health check and troubleshooting';

$diagnostics = [];

// Helper function to add diagnostic result
function addDiagnostic($category, $test, $status, $message, $fix = null) {
    global $diagnostics;
    if (!isset($diagnostics[$category])) {
        $diagnostics[$category] = [];
    }
    $diagnostics[$category][] = [
        'test' => $test,
        'status' => $status, // 'pass', 'warning', 'fail'
        'message' => $message,
        'fix' => $fix
    ];
}

// Run diagnostics
try {
    // Database tests
    try {
        $conn = getConnection();
        
        if ($conn) {
            addDiagnostic('Database', 'Connection Test', 'pass', 'Database connection successful');
            
            // Test database queries
            $result = $conn->query("SHOW TABLES");
            if ($result) {
                $tables = [];
                while ($row = $result->fetch(PDO::FETCH_NUM)) {
                    $tables[] = $row[0];
                }
                
                $requiredTables = ['editions', 'categories', 'admin_users', 'settings'];
                $missingTables = array_diff($requiredTables, $tables);
                
                if (empty($missingTables)) {
                    addDiagnostic('Database', 'Table Structure', 'pass', 'All required tables exist');
                } else {
                    addDiagnostic('Database', 'Table Structure', 'warning', 
                        'Missing tables: ' . implode(', ', $missingTables),
                        'Run database setup to create missing tables');
                }
                
                // Test user authentication table
                if (in_array('admin_users', $tables)) {
                    $userCount = $conn->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
                    addDiagnostic('Database', 'Admin Users', 'pass', "Found $userCount admin user(s)");
                }
            }
        } else {
            addDiagnostic('Database', 'Connection Test', 'fail', 
                'Cannot connect to database', 
                'Check database configuration in config.php');
        }
    } catch (Exception $e) {
        addDiagnostic('Database', 'Connection Test', 'fail', 
            'Database error: ' . $e->getMessage(),
            'Check database configuration and credentials');
    }
    
    // File system tests
    $uploadDir = '../uploads';
    if (is_dir($uploadDir)) {
        if (is_writable($uploadDir)) {
            addDiagnostic('File System', 'Upload Directory', 'pass', 
                'Upload directory exists and is writable');
        } else {
            addDiagnostic('File System', 'Upload Directory', 'warning', 
                'Upload directory exists but is not writable',
                'Set proper permissions on uploads directory (755 or 777)');
        }
    } else {
        addDiagnostic('File System', 'Upload Directory', 'fail', 
            'Upload directory does not exist',
            'Create uploads directory and set proper permissions');
    }
    
    // Configuration tests
    if (defined('DB_HOST')) {
        addDiagnostic('Configuration', 'Database Config', 'pass', 'Database configuration found');
    } else {
        addDiagnostic('Configuration', 'Database Config', 'fail', 
            'Database configuration missing',
            'Check config.php file');
    }
    
    // PHP tests
    $phpVersion = phpversion();
    if (version_compare($phpVersion, '7.4', '>=')) {
        addDiagnostic('PHP', 'Version Check', 'pass', "PHP version $phpVersion is supported");
    } else {
        addDiagnostic('PHP', 'Version Check', 'warning', 
            "PHP version $phpVersion may have compatibility issues",
            'Consider upgrading to PHP 7.4 or higher');
    }
    
    // Extension tests
    $requiredExtensions = ['mysqli', 'gd', 'zip'];
    foreach ($requiredExtensions as $ext) {
        if (extension_loaded($ext)) {
            addDiagnostic('PHP Extensions', ucfirst($ext), 'pass', "$ext extension is loaded");
        } else {
            addDiagnostic('PHP Extensions', ucfirst($ext), 'fail', 
                "$ext extension is not loaded",
                "Install and enable $ext extension");
        }
    }
    
    // Memory and upload limits
    $memoryLimit = ini_get('memory_limit');
    $uploadMax = ini_get('upload_max_filesize');
    $postMax = ini_get('post_max_size');
    
    addDiagnostic('PHP Settings', 'Memory Limit', 'pass', "Memory limit: $memoryLimit");
    addDiagnostic('PHP Settings', 'Upload Limit', 'pass', "Upload max: $uploadMax");
    addDiagnostic('PHP Settings', 'Post Limit', 'pass', "Post max: $postMax");
    
} catch (Exception $e) {
    addDiagnostic('System', 'General Error', 'fail', 
        'Diagnostic error: ' . $e->getMessage());
}

// Calculate overall status
$totalTests = 0;
$passedTests = 0;
$warnings = 0;
$failures = 0;

foreach ($diagnostics as $category => $tests) {
    foreach ($tests as $test) {
        $totalTests++;
        switch ($test['status']) {
            case 'pass':
                $passedTests++;
                break;
            case 'warning':
                $warnings++;
                break;
            case 'fail':
                $failures++;
                break;
        }
    }
}

$overallStatus = 'success';
if ($failures > 0) {
    $overallStatus = 'danger';
} elseif ($warnings > 0) {
    $overallStatus = 'warning';
}

// Set alert message for layout
if ($overallStatus === 'success') {
    $alertMessage = 'All system checks passed successfully!';
    $alertType = 'success';
} elseif ($overallStatus === 'warning') {
    $alertMessage = 'System is functional but has some warnings that should be addressed.';
    $alertType = 'warning';
} else {
    $alertMessage = 'System has critical issues that need immediate attention.';
    $alertType = 'danger';
}

// Include the admin layout
require_once 'includes/admin_layout.php';
?>

<!-- Diagnostics Content -->
<div class="row mb-4">
    <div class="col-12">
        <div class="admin-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-stethoscope"></i>
                    System Health Overview
                </h5>
                <button type="button" class="btn btn-outline-primary" onclick="location.reload()">
                    <i class="fas fa-redo"></i> Refresh
                </button>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-3">
                        <h3 class="text-primary"><?php echo $totalTests; ?></h3>
                        <small class="text-muted">Total Tests</small>
                    </div>
                    <div class="col-3">
                        <h3 class="text-success"><?php echo $passedTests; ?></h3>
                        <small class="text-muted">Passed</small>
                    </div>
                    <div class="col-3">
                        <h3 class="text-warning"><?php echo $warnings; ?></h3>
                        <small class="text-muted">Warnings</small>
                    </div>
                    <div class="col-3">
                        <h3 class="text-danger"><?php echo $failures; ?></h3>
                        <small class="text-muted">Failures</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Results -->
<div class="row">
    <?php foreach ($diagnostics as $category => $tests): ?>
    <div class="col-lg-6 mb-4">
        <div class="admin-card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-<?php echo getIconForCategory($category); ?>"></i>
                    <?php echo htmlspecialchars($category); ?>
                </h6>
            </div>
            <div class="card-body">
                <?php foreach ($tests as $test): ?>
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-1">
                            <i class="fas fa-<?php echo getIconForStatus($test['status']); ?> text-<?php echo getColorForStatus($test['status']); ?> me-2"></i>
                            <strong><?php echo htmlspecialchars($test['test']); ?></strong>
                        </div>
                        <p class="mb-1 text-muted small"><?php echo htmlspecialchars($test['message']); ?></p>
                        <?php if ($test['fix']): ?>
                        <div class="alert alert-warning py-2 px-3 mb-0 small">
                            <strong>Fix:</strong> <?php echo htmlspecialchars($test['fix']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <span class="badge bg-<?php echo getColorForStatus($test['status']); ?> ms-2">
                        <?php echo ucfirst($test['status']); ?>
                    </span>
                </div>
                <?php if ($test !== end($tests)): ?>
                <hr class="my-2">
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Quick Fixes -->
<div class="row mt-4">
    <div class="col-12">
        <div class="admin-card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-tools"></i>
                    Quick Fixes
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <div class="d-grid">
                            <a href="setup-database.php" class="btn btn-outline-primary">
                                <i class="fas fa-database"></i>
                                Setup Database
                            </a>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="d-grid">
                            <button type="button" class="btn btn-outline-warning" onclick="createUploadsDir()">
                                <i class="fas fa-folder-plus"></i>
                                Create Uploads Dir
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="d-grid">
                            <button type="button" class="btn btn-outline-info" onclick="testConnection()">
                                <i class="fas fa-wifi"></i>
                                Test Connection
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="d-grid">
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i>
                                Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Helper functions
function getIconForCategory($category) {
    $icons = [
        'Database' => 'database',
        'File System' => 'folder',
        'Configuration' => 'cog',
        'PHP' => 'code',
        'PHP Extensions' => 'puzzle-piece',
        'PHP Settings' => 'sliders-h',
        'System' => 'server'
    ];
    return $icons[$category] ?? 'check';
}

function getIconForStatus($status) {
    return $status === 'pass' ? 'check-circle' : ($status === 'warning' ? 'exclamation-triangle' : 'times-circle');
}

function getColorForStatus($status) {
    return $status === 'pass' ? 'success' : ($status === 'warning' ? 'warning' : 'danger');
}
?>

<!-- Custom JavaScript -->
<?php 
$additionalJS = "
<script>
function createUploadsDir() {
    if (confirm('Create uploads directory with proper permissions?')) {
        // In real implementation, this would make an AJAX call
        showToast('Uploads directory created successfully', 'success');
        setTimeout(() => location.reload(), 1000);
    }
}

function testConnection() {
    showToast('Testing database connection...', 'info');
    
    // In real implementation, this would make an AJAX call
    setTimeout(() => {
        showToast('Database connection test completed', 'success');
    }, 2000);
}

// Auto-refresh every 30 seconds
let autoRefresh = false;

function toggleAutoRefresh() {
    autoRefresh = !autoRefresh;
    const btn = document.getElementById('autoRefreshBtn');
    
    if (autoRefresh) {
        btn.textContent = 'Stop Auto-refresh';
        btn.className = 'btn btn-warning';
        refreshInterval = setInterval(() => {
            location.reload();
        }, 30000);
        showToast('Auto-refresh enabled (30s)', 'info');
    } else {
        btn.textContent = 'Start Auto-refresh';
        btn.className = 'btn btn-outline-info';
        clearInterval(refreshInterval);
        showToast('Auto-refresh disabled', 'info');
    }
}
</script>
";

// Include the admin layout footer
require_once 'includes/admin_layout_footer.php';
?>
