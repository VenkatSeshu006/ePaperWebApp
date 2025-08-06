<?php
/**
 * Admin Dashboard
 * Main administrative interface for E-Paper CMS
 */

// Suppress PHP warnings for cleaner dashboard display
error_reporting(E_ERROR | E_PARSE);

session_start();
define('ADMIN_PAGE', true);

// Include configuration first
require_once '../config/config.php';
require_once '../includes/database.php';

// Simple authentication check
$isAuthenticated = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Handle login
if (!$isAuthenticated && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Database authentication
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT id, username, full_name, password_hash, role FROM admin_users WHERE username = ? AND role IN ('admin', 'editor')");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Valid credentials - create session
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user'] = $user['username'];
            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_full_name'] = $user['full_name'];
            $_SESSION['admin_role'] = $user['role'];
            
            // Update last login time
            $updateStmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);
            
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $loginError = 'Invalid username or password';
        }
    } catch (Exception $e) {
        $loginError = 'Login system error. Please try again.';
        error_log("Login error: " . $e->getMessage());
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// If not authenticated, show login form
if (!$isAuthenticated) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - E-Paper CMS</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            .login-card {
                background: white;
                border-radius: 15px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.2);
                padding: 40px;
                max-width: 400px;
                width: 100%;
            }
            .login-header {
                text-align: center;
                margin-bottom: 30px;
            }
            .login-header i {
                font-size: 3rem;
                color: #667eea;
                margin-bottom: 15px;
            }
            .login-header h2 {
                color: #333;
                font-weight: 600;
                margin-bottom: 5px;
            }
            .login-header p {
                color: #666;
                font-size: 0.9rem;
            }
            .form-control {
                border-radius: 8px;
                padding: 12px 15px;
                border: 2px solid #eee;
                font-size: 1rem;
            }
            .form-control:focus {
                border-color: #667eea;
                box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            }
            .btn-login {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border: none;
                border-radius: 8px;
                padding: 12px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 1px;
                transition: all 0.3s ease;
            }
            .btn-login:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            }
            .input-group-text {
                background-color: #f8f9fa;
                border: 2px solid #eee;
                border-right: none;
            }
            .form-control {
                border-left: none;
            }
            
            /* Tool Cards Styles */
            .tool-card {
                background: #fff;
                border: 1px solid #e9ecef;
                border-radius: 12px;
                transition: all 0.3s ease;
                box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            }
            
            .tool-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 30px rgba(0,0,0,0.15);
                border-color: #007bff;
            }
            
            .tool-card .btn {
                border-radius: 8px;
                font-weight: 500;
                transition: all 0.3s ease;
            }
            
            .tool-card .btn:hover {
                transform: translateY(-2px);
            }
        </style>
    </head>
    <body>
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-newspaper"></i>
                <h2>E-Paper CMS</h2>
                <p>Administration Panel</p>
            </div>
            
            <?php if (isset($loginError)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $loginError; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" class="form-control" name="username" placeholder="Username" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" name="password" placeholder="Password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login w-100">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
                
                <div class="text-center mt-3">
                    <small class="text-muted">Default: admin / admin123</small>
                </div>
            </form>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    exit;
}

// Page configuration for authenticated users
$pageTitle = 'Dashboard';
$pageSubtitle = 'System overview and statistics';

// Initialize statistics
$stats = [
    'total_editions' => 0,
    'total_views' => 0,
    'monthly_views' => 0,
    'storage_used' => 0,
    'watermark_enabled' => false
];

try {
    require_once '../includes/database.php';
    $conn = getConnection();
    
    if ($conn) {
        // Get total editions
        $result = $conn->query("SELECT COUNT(*) as count FROM editions");
        if ($result) {
            $stats['total_editions'] = $result->fetch()['count'] ?? 0;
        }
        
        // Get total views
        $result = $conn->query("SELECT SUM(views) as total FROM editions");
        if ($result) {
            $stats['total_views'] = $result->fetch()['total'] ?? 0;
        }
        
        // Get monthly views (current month)
        $currentMonth = date('Y-m');
        $result = $conn->query("SELECT SUM(views) as monthly FROM editions WHERE DATE_FORMAT(created_at, '%Y-%m') = '$currentMonth'");
        if ($result) {
            $stats['monthly_views'] = $result->fetch()['monthly'] ?? 0;
        }
        
        // Check watermark status
        $result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'watermark_enabled'");
        if ($result) {
            $watermarkSetting = $result->fetch();
            $stats['watermark_enabled'] = ($watermarkSetting && $watermarkSetting['setting_value'] === '1');
        }
        
        // Calculate storage used
        $uploadsPath = '../uploads';
        if (is_dir($uploadsPath)) {
            $stats['storage_used'] = getDirectorySize($uploadsPath);
        }
    }
} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
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

// Recent activity
$recentEditions = [];
try {
    if (isset($conn)) {
        $result = $conn->query("SELECT id, title, description, date, cover_image, views, status, created_at FROM editions ORDER BY created_at DESC LIMIT 5");
        if ($result) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $recentEditions[] = $row;
            }
        }
    }
} catch (Exception $e) {
    error_log("Recent editions error: " . $e->getMessage());
}

// Include the admin layout
require_once 'includes/admin_layout.php';
?>

<!-- Welcome Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info" role="alert">
            <h4 class="alert-heading">
                <i class="fas fa-tachometer-alt"></i>
                Welcome to E-Paper CMS Dashboard
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

<!-- Dashboard Content -->
<div class="row mb-4">
    <!-- Statistics Cards -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="admin-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Editions
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($stats['total_editions']); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-newspaper fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="admin-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Total Views
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($stats['total_views']); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-eye fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="admin-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Monthly Views
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($stats['monthly_views']); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="admin-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Storage Used
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo formatBytes($stats['storage_used']); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-hdd fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Editions -->
    <div class="col-lg-8 mb-4">
        <div class="admin-card">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Recent Editions</h6>
                <a href="manage_editions.php" class="btn btn-sm btn-admin-primary">
                    <i class="fas fa-eye"></i> View All
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recentEditions)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-gray-300 mb-3"></i>
                        <h5 class="text-gray-500">No editions yet</h5>
                        <p class="text-muted">Start by uploading your first edition</p>
                        <a href="upload.php" class="btn btn-admin-primary">
                            <i class="fas fa-upload"></i> Upload Edition
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Date</th>
                                    <th>Views</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentEditions as $edition): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($edition['cover_image']) && file_exists("../" . $edition['cover_image'])): ?>
                                                <img src="../<?php echo htmlspecialchars($edition['cover_image'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                     alt="Thumbnail" class="rounded me-2" 
                                                     style="width: 40px; height: 40px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="rounded me-2 bg-light d-flex align-items-center justify-content-center" 
                                                     style="width: 40px; height: 40px;">
                                                    <i class="fas fa-newspaper text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <strong><?php echo htmlspecialchars($edition['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($edition['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo isset($edition['created_at']) ? date('M j, Y', strtotime($edition['created_at'])) : 'N/A'; ?></td>
                                    <td><?php echo number_format($edition['views'] ?? 0); ?></td>
                                    <td>
                                        <?php 
                                        $status = $edition['status'] ?? 'draft';
                                        $badgeClass = $status === 'published' ? 'bg-success' : ($status === 'draft' ? 'bg-warning' : 'bg-secondary');
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="../view-edition.php?id=<?php echo $edition['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" target="_blank" title="View Edition">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_edition.php?id=<?php echo $edition['id']; ?>" 
                                               class="btn btn-sm btn-outline-secondary" title="Edit Edition">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-lg-4 mb-4">
        <div class="admin-card">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="upload.php" class="btn btn-admin-primary">
                        <i class="fas fa-upload"></i> Upload New Edition
                    </a>
                    <a href="manage_editions.php" class="btn btn-outline-primary">
                        <i class="fas fa-newspaper"></i> Manage Editions
                    </a>
                    <a href="categories.php" class="btn btn-outline-secondary">
                        <i class="fas fa-tags"></i> Manage Categories
                    </a>
                    <a href="settings.php" class="btn btn-outline-info">
                        <i class="fas fa-cog"></i> System Settings
                    </a>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="admin-card mt-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">System Status</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small">Database</span>
                        <span class="badge bg-success">Online</span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small">File Uploads</span>
                        <span class="badge bg-success">Working</span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small">PDF Processing</span>
                        <span class="badge bg-success">Available</span>
                    </div>
                </div>
                <hr>
                <div class="text-center">
                    <a href="diagnostics.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-stethoscope"></i> Run Diagnostics
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Admin Tools Grid -->
<div class="row mb-4">
    <div class="col-12">
        <div class="admin-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-tools"></i>
                    Administrative Tools
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Content Management -->
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="tool-card h-100">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="fas fa-newspaper fa-3x text-primary"></i>
                                </div>
                                <h5 class="card-title">Content Management</h5>
                                <p class="card-text text-muted">Upload, manage, and organize your newspaper editions</p>
                                <div class="d-grid gap-2">
                                    <a href="upload.php" class="btn btn-primary">
                                        <i class="fas fa-upload"></i> Upload Edition
                                    </a>
                                    <a href="manage_editions.php" class="btn btn-outline-primary">
                                        <i class="fas fa-list"></i> Manage Editions
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Watermark & Branding -->
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="tool-card h-100">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="fas fa-image fa-3x text-info"></i>
                                </div>
                                <h5 class="card-title">Watermark & Branding</h5>
                                <p class="card-text text-muted">Configure publisher watermark for clip downloads</p>
                                <div class="d-grid gap-2">
                                    <a href="watermark_settings.php" class="btn btn-info">
                                        <i class="fas fa-cog"></i> Watermark Settings
                                    </a>
                                    <div class="small mt-2">
                                        Status: 
                                        <span class="badge <?php echo $stats['watermark_enabled'] ? 'bg-success' : 'bg-warning'; ?>">
                                            <?php echo $stats['watermark_enabled'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Organization -->
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="tool-card h-100">
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
                                    <a href="clips.php" class="btn btn-outline-success">
                                        <i class="fas fa-cut"></i> View Clips
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System & Settings -->
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="tool-card h-100">
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
                                    <a href="page_settings.php" class="btn btn-outline-warning">
                                        <i class="fas fa-edit"></i> Page Settings
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
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
                    <i class="fas fa-wrench"></i>
                    System Tools & Utilities
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
                            <a href="diagnostics.php" class="btn btn-outline-secondary">
                                <i class="fas fa-stethoscope"></i>
                                <br>
                                <small>System Diagnostics</small>
                            </a>
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

<!-- System Information Footer -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card bg-light">
            <div class="card-body text-center py-2">
                <h6 class="card-title mb-1">
                    <i class="fas fa-server text-muted"></i>
                    System Information
                </h6>
                <small class="text-muted">
                    <?php
                    echo "PHP Version: " . phpversion() . " | ";
                    echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . " | ";
                    echo "Database: MySQL " . ($conn ? "Connected" : "Disconnected") . " | ";
                    echo "Last Updated: " . date('Y-m-d H:i:s');
                    ?>
                </small>
            </div>
        </div>
    </div>
</div>

<?php
// Include the admin layout footer
require_once 'includes/admin_layout_footer.php';
?>
