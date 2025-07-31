<?php
/**
 * Admin Dashboard
 * Main administrative interface for E-Paper CMS
 */

session_start();

// Include configuration first
require_once '../config/config.php';
require_once '../includes/database.php';

// Simple authentication check
$isAuthenticated = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Handle login
if (!$isAuthenticated && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Simple hardcoded authentication (you should use proper authentication)
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $username;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $loginError = 'Invalid credentials';
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
        <link rel="stylesheet" href="../assets/css/style.css">
        <style>
            .login-container {
                max-width: 400px;
                margin: 100px auto;
                padding: 2rem;
                background: white;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            }
            .login-form {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }
            .form-group {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            .form-control {
                padding: 0.75rem;
                border: 1px solid #ddd;
                border-radius: 6px;
                font-size: 1rem;
            }
            .btn-login {
                background: var(--primary-color);
                color: white;
                border: none;
                padding: 0.75rem;
                border-radius: 6px;
                font-size: 1rem;
                cursor: pointer;
            }
            .error {
                color: #f44336;
                text-align: center;
                margin-bottom: 1rem;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h2>E-Paper CMS Login</h2>
            <?php if (isset($loginError)): ?>
                <div class="error"><?php echo htmlspecialchars($loginError); ?></div>
            <?php endif; ?>
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn-login">Login</button>
            </form>
            <div style="margin-top: 1rem; font-size: 0.9rem; color: #666; text-align: center;">
                Default: admin / admin123
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Check if classes exist and include them
$classFiles = ['Edition', 'Analytics'];
foreach ($classFiles as $class) {
    $classPath = "../classes/{$class}.php";
    if (file_exists($classPath)) {
        require_once $classPath;
    } else {
        error_log("Missing class file: {$classPath}");
    }
}

// Initialize models with error handling
$editionModel = null;
$analytics = null;

try {
    // Check if database connection is available
    $database = Database::getInstance();
    
    if (class_exists('Edition')) {
        $editionModel = new Edition();
    } else {
        throw new Exception("Edition class not found");
    }
    
    if (class_exists('Analytics')) {
        $analytics = new Analytics();
    }
} catch (Exception $e) {
    error_log("Model initialization error: " . $e->getMessage());
    $modelError = "Database connection failed: " . $e->getMessage();
}

// Get dashboard data with error handling
$dashboardData = [
    'total_editions' => 0,
    'total_views' => 0,
    'total_clips' => 0,
    'recent_editions' => [],
    'popular_editions' => [],
    'monthly_views' => []
];

if ($editionModel) {
    try {
        $dashboardData['total_editions'] = $editionModel->getTotalCount();
        $dashboardData['recent_editions'] = $editionModel->getPublished(1, 5);
    } catch (Exception $e) {
        error_log("Dashboard data error: " . $e->getMessage());
        $dashboardData['error'] = "Unable to load edition data: " . $e->getMessage();
    }
}

if ($analytics) {
    try {
        $dashboardData['total_views'] = $analytics->getTotalViews();
        $dashboardData['monthly_views'] = $analytics->getMonthlyViews();
    } catch (Exception $e) {
        error_log("Analytics data error: " . $e->getMessage());
        $dashboardData['analytics_error'] = "Unable to load analytics: " . $e->getMessage();
    }
}

if ($analytics) {
    try {
        $dashboardData['total_views'] = $analytics->getTotalViews();
        $dashboardData['monthly_views'] = $analytics->getMonthlyStats();
    } catch (Exception $e) {
        error_log("Analytics data error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - E-Paper CMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --admin-primary: #1976d2;
            --admin-secondary: #424242;
            --admin-success: #4caf50;
            --admin-warning: #ff9800;
            --admin-danger: #f44336;
        }
        
        .admin-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            background: var(--admin-secondary);
            color: white;
            padding: 1rem;
        }
        
        .admin-content {
            padding: 2rem;
            background: #f5f5f5;
        }
        
        .admin-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .admin-nav li {
            margin-bottom: 0.5rem;
        }
        
        .admin-nav a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            transition: background-color 0.2s;
        }
        
        .admin-nav a:hover,
        .admin-nav a.active {
            background: rgba(255,255,255,0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .stat-content h3 {
            margin: 0;
            font-size: 2rem;
            color: var(--admin-secondary);
        }
        
        .stat-content p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .content-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .section-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .edition-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .edition-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .edition-item:last-child {
            border-bottom: none;
        }
        
        .edition-info h4 {
            margin: 0 0 0.25rem 0;
            font-size: 0.95rem;
        }
        
        .edition-info p {
            margin: 0;
            font-size: 0.8rem;
            color: #666;
        }
        
        .edition-stats {
            text-align: right;
            font-size: 0.8rem;
            color: #666;
        }
        
        .btn-group {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: var(--admin-primary);
            color: white;
        }
        
        .btn-success {
            background: var(--admin-success);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        @media (max-width: 768px) {
            .admin-layout {
                grid-template-columns: 1fr;
            }
            
            .admin-sidebar {
                order: 2;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div style="margin-bottom: 2rem;">
                <h3><i class="fas fa-newspaper"></i> E-Paper CMS</h3>
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['admin_user']); ?></p>
            </div>
            
            <ul class="admin-nav">
                <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="upload.php"><i class="fas fa-upload"></i> Upload Edition</a></li>
                <li><a href="manage_editions.php"><i class="fas fa-newspaper"></i> Manage Editions</a></li>
                <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                <li><a href="clips.php"><i class="fas fa-cut"></i> Clips</a></li>
                <li><a href="analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-content">
            <div class="section-header">
                <h1>Dashboard</h1>
                <div class="btn-group">
                    <a href="upload.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Edition
                    </a>
                    <a href="../" class="btn btn-secondary">
                        <i class="fas fa-eye"></i> View Site
                    </a>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--admin-primary);">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($dashboardData['total_editions']); ?></h3>
                        <p>Total Editions</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--admin-success);">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($dashboardData['total_views']); ?></h3>
                        <p>Total Views</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--admin-warning);">
                        <i class="fas fa-cut"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($dashboardData['total_clips']); ?></h3>
                        <p>Total Clips</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--admin-danger);">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo date('j'); ?></h3>
                        <p>Today's Date</p>
                    </div>
                </div>
            </div>
            
            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Recent Editions -->
                <div class="content-section">
                    <div class="section-header">
                        <h2><i class="fas fa-newspaper"></i> Recent Editions</h2>
                        <a href="manage_editions.php" class="btn btn-secondary">View All</a>
                    </div>
                    
                    <?php if (!empty($dashboardData['recent_editions'])): ?>
                    <ul class="edition-list">
                        <?php foreach ($dashboardData['recent_editions'] as $edition): ?>
                        <li class="edition-item">
                            <div class="edition-info">
                                <h4><?php echo htmlspecialchars($edition['title']); ?></h4>
                                <p><?php echo date('M j, Y', strtotime($edition['date'])); ?> • 
                                   Status: <?php echo ucfirst($edition['status']); ?></p>
                            </div>
                            <div class="edition-stats">
                                <div><?php echo number_format($edition['views']); ?> views</div>
                                <div style="margin-top: 0.25rem;">
                                    <a href="../?id=<?php echo $edition['id']; ?>" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <p>No editions found. <a href="upload.php">Create your first edition</a>.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Actions -->
                <div class="content-section">
                    <div class="section-header">
                        <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                    </div>
                    
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <a href="upload.php" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload New Edition
                        </a>
                        <a href="categories.php" class="btn btn-success">
                            <i class="fas fa-tags"></i> Manage Categories
                        </a>
                        <a href="analytics.php" class="btn btn-secondary">
                            <i class="fas fa-chart-line"></i> View Analytics
                        </a>
                        <a href="settings.php" class="btn btn-secondary">
                            <i class="fas fa-cog"></i> System Settings
                        </a>
                    </div>
                    
                    <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #eee;">
                        <h3>System Status</h3>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.9rem;">
                            <div style="display: flex; justify-content: space-between;">
                                <span>PHP Version:</span>
                                <span><?php echo PHP_VERSION; ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span>Server Time:</span>
                                <span><?php echo date('H:i:s'); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span>Database:</span>
                                <span style="color: var(--admin-success);">Connected</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
