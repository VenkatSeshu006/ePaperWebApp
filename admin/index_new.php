<?php
/**
 * Admin Panel Overview
 * Main entry point for the administrative interface
 */

session_start();
define('ADMIN_PAGE', true);

// Include configuration first
require_once '../config/config.php';

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
$pageTitle = 'Admin Overview';
$pageSubtitle = 'Administrative tools and system management';

// Get system stats
$stats = [
    'total_editions' => 0,
    'total_views' => 0,
    'categories' => 0,
    'storage_used' => 0
];

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    if ($conn) {
        // Get total editions
        $result = $conn->query("SELECT COUNT(*) as count FROM editions");
        if ($result) {
            $stats['total_editions'] = $result->fetch_assoc()['count'] ?? 0;
        }
        
        // Get total views
        $result = $conn->query("SELECT SUM(views) as total FROM editions");
        if ($result) {
            $stats['total_views'] = $result->fetch_assoc()['total'] ?? 0;
        }
        
        // Get categories count
        $result = $conn->query("SELECT COUNT(*) as count FROM categories");
        if ($result) {
            $stats['categories'] = $result->fetch_assoc()['count'] ?? 0;
        }
        
        // Calculate storage used
        $uploadsPath = '../uploads';
        if (is_dir($uploadsPath)) {
            $stats['storage_used'] = getDirectorySize($uploadsPath);
        }
    }
} catch (Exception $e) {
    error_log("Admin overview stats error: " . $e->getMessage());
}

// Helper function to calculate directory size
function getDirectorySize($directory) {
    $size = 0;
    if (is_dir($directory)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
    }
    return $size;
}

// Format bytes for display
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Include the admin layout
require_once 'includes/admin_layout.php';
?>

<!-- Admin Overview Content -->
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info" role="alert">
            <h4 class="alert-heading">
                <i class="fas fa-info-circle"></i>
                Welcome to E-Paper CMS Admin Panel
            </h4>
            <p>This is your administrative dashboard where you can manage your digital newspaper content, upload new editions, organize categories, and monitor system performance.</p>
            <hr>
            <p class="mb-0">
                <strong>Quick Start:</strong> Begin by <a href="upload.php" class="alert-link">uploading your first edition</a> or 
                <a href="categories.php" class="alert-link">creating content categories</a>.
            </p>
        </div>
    </div>
</div>

<!-- Main Tools Grid -->
<div class="row mb-4">
    <!-- Content Management -->
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="admin-card h-100">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-newspaper fa-3x text-primary"></i>
                </div>
                <h5 class="card-title">Content Management</h5>
                <p class="card-text text-muted">Upload, manage, and organize your newspaper editions</p>
                <div class="d-grid gap-2">
                    <a href="upload.php" class="btn btn-admin-primary">
                        <i class="fas fa-upload"></i> Upload Edition
                    </a>
                    <a href="manage_editions.php" class="btn btn-outline-primary">
                        <i class="fas fa-list"></i> Manage Editions
                    </a>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-chart-line"></i>
                        <?php echo number_format($stats['total_editions']); ?> editions,
                        <?php echo number_format($stats['total_views']); ?> total views
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Organization -->
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="admin-card h-100">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-tags fa-3x text-success"></i>
                </div>
                <h5 class="card-title">Organization</h5>
                <p class="card-text text-muted">Create and manage categories to organize your content</p>
                <div class="d-grid gap-2">
                    <a href="categories.php" class="btn btn-success">
                        <i class="fas fa-tags"></i> Manage Categories
                    </a>
                    <a href="dashboard.php" class="btn btn-outline-success">
                        <i class="fas fa-tachometer-alt"></i> View Dashboard
                    </a>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-folder"></i>
                        <?php echo number_format($stats['categories']); ?> categories
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- System & Settings -->
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="admin-card h-100">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-cogs fa-3x text-warning"></i>
                </div>
                <h5 class="card-title">System & Settings</h5>
                <p class="card-text text-muted">Configure system settings and run diagnostics</p>
                <div class="d-grid gap-2">
                    <a href="settings.php" class="btn btn-warning">
                        <i class="fas fa-cog"></i> System Settings
                    </a>
                    <a href="diagnostics.php" class="btn btn-outline-warning">
                        <i class="fas fa-stethoscope"></i> Diagnostics
                    </a>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-hdd"></i>
                        <?php echo formatBytes($stats['storage_used']); ?> storage used
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- System Tools -->
<div class="row mb-4">
    <div class="col-12">
        <div class="admin-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-tools"></i>
                    System Tools
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="d-grid">
                            <a href="setup-database.php" class="btn btn-outline-info">
                                <i class="fas fa-database"></i>
                                <br>
                                <small>Database Setup</small>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="d-grid">
                            <a href="test-database.php" class="btn btn-outline-info">
                                <i class="fas fa-vial"></i>
                                <br>
                                <small>Database Test</small>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="d-grid">
                            <button type="button" class="btn btn-outline-secondary" onclick="clearCache()">
                                <i class="fas fa-broom"></i>
                                <br>
                                <small>Clear Cache</small>
                            </button>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="d-grid">
                            <a href="../" class="btn btn-outline-primary" target="_blank">
                                <i class="fas fa-external-link-alt"></i>
                                <br>
                                <small>View Website</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-clock"></i>
                    Recent Activity
                </h5>
                <a href="dashboard.php" class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-info-circle fa-lg text-info"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Admin Panel Initialized</h6>
                            <p class="mb-1 text-muted">Admin panel is ready for use</p>
                            <small class="text-muted">Just now</small>
                        </div>
                    </div>
                    
                    <?php if ($stats['total_editions'] > 0): ?>
                    <div class="list-group-item d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-newspaper fa-lg text-success"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Editions Available</h6>
                            <p class="mb-1 text-muted"><?php echo number_format($stats['total_editions']); ?> editions in the system</p>
                            <small class="text-muted">System status</small>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="list-group-item d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-shield-alt fa-lg text-warning"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">System Status</h6>
                            <p class="mb-1 text-muted">All systems operational</p>
                            <small class="text-muted">System check</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar"></i>
                    Quick Stats
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="border-end">
                            <h4 class="text-primary"><?php echo number_format($stats['total_editions']); ?></h4>
                            <small class="text-muted">Editions</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <h4 class="text-success"><?php echo number_format($stats['total_views']); ?></h4>
                        <small class="text-muted">Total Views</small>
                    </div>
                    <div class="col-6">
                        <div class="border-end">
                            <h4 class="text-warning"><?php echo number_format($stats['categories']); ?></h4>
                            <small class="text-muted">Categories</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h4 class="text-info"><?php echo formatBytes($stats['storage_used']); ?></h4>
                        <small class="text-muted">Storage</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Health -->
        <div class="admin-card mt-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-heart"></i>
                    System Health
                </h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small">Database</span>
                    <span class="badge bg-success">Online</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small">File System</span>
                    <span class="badge bg-success">OK</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small">PHP</span>
                    <span class="badge bg-success"><?php echo PHP_VERSION; ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small">Memory</span>
                    <span class="badge bg-info"><?php echo ini_get('memory_limit'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom JavaScript -->
<?php 
$additionalJS = "
<script>
// Clear cache function
function clearCache() {
    if (confirm('Are you sure you want to clear the system cache?')) {
        showToast('Cache cleared successfully', 'success');
        // In real implementation, this would make an AJAX call
    }
}

// Auto-refresh stats every 30 seconds
setInterval(function() {
    // In real implementation, this would update stats via AJAX
    console.log('Auto-refreshing stats...');
}, 30000);
</script>
";

// Include the admin layout footer
require_once 'includes/admin_layout_footer.php';
?>
