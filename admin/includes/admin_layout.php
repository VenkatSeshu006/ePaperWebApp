<?php
/**
 * Admin Layout Template
 * Provides consistent layout and navigation for all admin pages
 */

// Ensure this is included in an admin context
if (!defined('ADMIN_PAGE')) {
    die('Direct access not allowed');
}

// Get current page for navigation highlighting
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Check authentication status
$isAuthenticated = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$adminUser = $_SESSION['admin_user'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin Panel'; ?> - E-Paper CMS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Admin Styles -->
    <style>
        :root {
            --admin-primary: #2c3e50;
            --admin-secondary: #34495e;
            --admin-accent: #3498db;
            --admin-success: #27ae60;
            --admin-warning: #f39c12;
            --admin-danger: #e74c3c;
            --admin-light: #ecf0f1;
            --admin-dark: #2c3e50;
            --sidebar-width: 250px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            overflow-x: hidden;
        }

        /* Admin Layout Container */
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .admin-sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--admin-primary) 0%, var(--admin-secondary) 100%);
            color: white;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            overflow-y: auto;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .admin-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .admin-sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
        }

        .admin-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
        }

        /* Sidebar Header */
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .sidebar-header h4 {
            color: white;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .sidebar-header small {
            color: rgba(255,255,255,0.7);
            font-size: 0.85rem;
        }

        /* Navigation Menu */
        .sidebar-nav {
            padding: 20px 0;
        }

        .nav-section {
            margin-bottom: 30px;
        }

        .nav-section-title {
            color: rgba(255,255,255,0.6);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0 20px 10px;
            margin-bottom: 10px;
        }

        .nav-item {
            margin-bottom: 2px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--admin-accent);
        }

        .nav-link.active {
            background-color: rgba(52, 152, 219, 0.2);
            color: white;
            border-left-color: var(--admin-accent);
        }

        .nav-link i {
            width: 20px;
            margin-right: 12px;
            font-size: 1.1rem;
        }

        .nav-link span {
            font-weight: 500;
        }

        .nav-badge {
            margin-left: auto;
            background-color: var(--admin-accent);
            color: white;
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 10px;
        }

        /* User Info */
        .sidebar-user {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, rgba(0,0,0,0.15) 0%, rgba(0,0,0,0.25) 100%);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(255,255,255,0.15);
        }

        .user-profile {
            padding: 16px 20px 12px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--admin-accent) 0%, #2980b9 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 1rem;
            color: white;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.3);
            border: 2px solid rgba(255,255,255,0.2);
        }

        .user-details h6 {
            margin: 0 0 2px 0;
            font-size: 0.9rem;
            color: white;
            font-weight: 600;
        }

        .user-details small {
            color: rgba(255,255,255,0.8);
            font-size: 0.75rem;
            font-weight: 500;
        }

        .user-actions {
            padding: 8px 20px 16px;
        }

        .logout-link {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-size: 0.85rem;
            font-weight: 500;
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.1);
        }

        .logout-link:hover {
            background: rgba(231, 76, 60, 0.8);
            border-color: rgba(231, 76, 60, 0.8);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        }

        .logout-link i {
            margin-right: 8px;
            font-size: 0.8rem;
        }

        /* Main Content Area */
        .admin-main {
            flex: 1;
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Top Header */
        .admin-header {
            background: white;
            padding: 15px 30px;
            border-bottom: 1px solid #e9ecef;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 999;
        }

        .header-content {
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .page-title {
            margin: 0;
            color: var(--admin-dark);
            font-size: 1.5rem;
            font-weight: 600;
        }

        .page-subtitle {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 2px;
        }

        .header-actions {
            margin-left: auto;
            display: flex;
            gap: 10px;
        }

        /* Content Area */
        .admin-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }

            .admin-sidebar.mobile-open {
                transform: translateX(0);
            }

            .admin-main {
                margin-left: 0;
            }

            .admin-content {
                padding: 20px 15px;
            }

            .sidebar-toggle {
                display: block !important;
            }
        }

        .sidebar-toggle {
            display: none;
            background: var(--admin-primary);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
        }

        /* Alert Styles */
        .alert {
            border: none;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid var(--admin-success);
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border-left: 4px solid var(--admin-warning);
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--admin-danger);
        }

        .alert-info {
            background-color: #cce7f0;
            color: #055160;
            border-left: 4px solid var(--admin-accent);
        }

        /* Card Styles */
        .admin-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: none;
            margin-bottom: 20px;
        }

        .admin-card .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
        }

        .admin-card .card-body {
            padding: 20px;
        }

        /* Button Styles */
        .btn {
            border-radius: 6px;
            font-weight: 500;
            padding: 8px 16px;
            transition: all 0.3s ease;
        }

        .btn-admin-primary {
            background-color: var(--admin-accent);
            border-color: var(--admin-accent);
            color: white;
        }

        .btn-admin-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        /* Loading States */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--admin-accent);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 8px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    
    <?php if (isset($additionalCSS)): ?>
        <?php echo $additionalCSS; ?>
    <?php endif; ?>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <nav class="admin-sidebar" id="adminSidebar">
            <!-- Sidebar Header -->
            <div class="sidebar-header">
                <h4><i class="fas fa-newspaper"></i> E-Paper CMS</h4>
                <small>Administration Panel</small>
            </div>

            <!-- Navigation Menu -->
            <div class="sidebar-nav">
                <!-- Main Section -->
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <div class="nav-item">
                        <a href="dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="index.php" class="nav-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i>
                            <span>Overview</span>
                        </a>
                    </div>
                </div>

                <!-- Content Management -->
                <div class="nav-section">
                    <div class="nav-section-title">Content</div>
                    <div class="nav-item">
                        <a href="upload.php" class="nav-link <?php echo $currentPage === 'upload' ? 'active' : ''; ?>">
                            <i class="fas fa-upload"></i>
                            <span>Upload Editions</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="manage_editions.php" class="nav-link <?php echo $currentPage === 'manage_editions' ? 'active' : ''; ?>">
                            <i class="fas fa-newspaper"></i>
                            <span>Manage Editions</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="categories.php" class="nav-link <?php echo $currentPage === 'categories' ? 'active' : ''; ?>">
                            <i class="fas fa-tags"></i>
                            <span>Categories</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="clips.php" class="nav-link <?php echo $currentPage === 'clips' ? 'active' : ''; ?>">
                            <i class="fas fa-cut"></i>
                            <span>User Clips</span>
                        </a>
                    </div>
                </div>

                <!-- System -->
                <div class="nav-section">
                    <div class="nav-section-title">System</div>
                    <div class="nav-item">
                        <a href="settings.php" class="nav-link <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="page_settings.php" class="nav-link <?php echo $currentPage === 'page_settings' ? 'active' : ''; ?>">
                            <i class="fas fa-cogs"></i>
                            <span>Page Settings</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="diagnostics.php" class="nav-link <?php echo $currentPage === 'diagnostics' ? 'active' : ''; ?>">
                            <i class="fas fa-stethoscope"></i>
                            <span>Diagnostics</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="users.php" class="nav-link <?php echo $currentPage === 'users' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i>
                            <span>User Management</span>
                        </a>
                    </div>
                </div>

                <!-- Tools -->
                <div class="nav-section">
                    <div class="nav-section-title">Tools</div>
                    <div class="nav-item">
                        <a href="setup-database.php" class="nav-link <?php echo $currentPage === 'setup-database' ? 'active' : ''; ?>">
                            <i class="fas fa-database"></i>
                            <span>Database Setup</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="test-database.php" class="nav-link <?php echo $currentPage === 'test-database' ? 'active' : ''; ?>">
                            <i class="fas fa-vial"></i>
                            <span>Database Test</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="../" class="nav-link" target="_blank">
                            <i class="fas fa-external-link-alt"></i>
                            <span>View Website</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- User Info -->
            <?php if ($isAuthenticated): ?>
            <div class="sidebar-user">
                <div class="user-profile">
                    <div class="user-info">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-details">
                            <h6><?php echo htmlspecialchars($adminUser); ?></h6>
                            <small>Administrator</small>
                        </div>
                    </div>
                </div>
                <div class="user-actions">
                    <a href="?logout=1" class="logout-link" onclick="return confirm('Are you sure you want to logout?')">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </nav>

        <!-- Main Content Area -->
        <main class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <div class="header-content">
                    <button class="sidebar-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h1 class="page-title"><?php echo $pageTitle ?? 'Admin Panel'; ?></h1>
                        <?php if (isset($pageSubtitle)): ?>
                            <p class="page-subtitle"><?php echo $pageSubtitle; ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="header-actions">
                        <?php if (isset($headerActions)): ?>
                            <?php echo $headerActions; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="admin-content">
                <?php if (isset($alertMessage)): ?>
                    <div class="alert alert-<?php echo $alertType ?? 'info'; ?> alert-dismissible fade show" role="alert">
                        <?php echo $alertMessage; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Page Content Will Be Inserted Here -->
