<?php
/**
 * Admin Panel Index
 * Main entry point for admin functions
 */

session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - E-Paper CMS</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 15px; 
            padding: 30px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        h1 { 
            color: #333; 
            text-align: center; 
            margin-bottom: 30px; 
            font-size: 2.5em;
        }
        .admin-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 25px; 
            margin-top: 30px; 
        }
        .admin-card { 
            background: white; 
            border: 1px solid #e0e0e0; 
            border-radius: 12px; 
            padding: 25px; 
            text-align: center; 
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .admin-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
            border-color: #007bff;
        }
        .admin-card h3 { 
            color: #333; 
            margin-bottom: 15px; 
            font-size: 1.3em;
        }
        .admin-card p { 
            color: #666; 
            margin-bottom: 20px; 
            line-height: 1.5;
        }
        .btn { 
            display: inline-block; 
            padding: 12px 25px; 
            background: #007bff; 
            color: white; 
            text-decoration: none; 
            border-radius: 8px; 
            transition: background 0.3s; 
            font-weight: 500;
        }
        .btn:hover { 
            background: #0056b3; 
        }
        .btn-secondary { 
            background: #6c757d; 
        }
        .btn-secondary:hover { 
            background: #545b62; 
        }
        .btn-success { 
            background: #28a745; 
        }
        .btn-success:hover { 
            background: #1e7e34; 
        }
        .btn-warning { 
            background: #ffc107; 
            color: #333;
        }
        .btn-warning:hover { 
            background: #e0a800; 
        }
        .btn-danger { 
            background: #dc3545; 
        }
        .btn-danger:hover { 
            background: #c82333; 
        }
        .status-bar {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .status-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #28a745;
        }
        .status-dot.warning {
            background: #ffc107;
        }
        .status-dot.error {
            background: #dc3545;
        }
        .icon {
            font-size: 3em;
            margin-bottom: 15px;
            color: #007bff;
        }
        .tools-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-top: 30px;
        }
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .tool-link {
            display: block;
            padding: 15px;
            background: white;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            text-align: center;
            border: 1px solid #e0e0e0;
        }
        .tool-link:hover {
            background: #007bff;
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗞️ E-Paper CMS Admin Panel</h1>
        
        <div class="status-bar">
            <div class="status-item">
                <div class="status-dot"></div>
                <span>System Status: Online</span>
            </div>
            <div class="status-item">
                <div class="status-dot"></div>
                <span>Database: Connected</span>
            </div>
            <div class="status-item">
                <div class="status-dot warning"></div>
                <span>Environment: <?php echo defined('ENVIRONMENT') ? ENVIRONMENT : 'Unknown'; ?></span>
            </div>
        </div>
        
        <div class="admin-grid">
            <div class="admin-card">
                <div class="icon">📊</div>
                <h3>Dashboard</h3>
                <p>View system overview, statistics, and recent activity</p>
                <a href="dashboard.php" class="btn">Open Dashboard</a>
            </div>
            
            <div class="admin-card">
                <div class="icon">📤</div>
                <h3>Upload Editions</h3>
                <p>Upload new newspaper editions and manage content</p>
                <a href="upload.php" class="btn btn-success">Upload Content</a>
            </div>
            
            <div class="admin-card">
                <div class="icon">📚</div>
                <h3>Manage Editions</h3>
                <p>Edit, delete, and organize existing editions</p>
                <a href="manage_editions.php" class="btn btn-secondary">Manage Editions</a>
            </div>
            
            <div class="admin-card">
                <div class="icon">🏷️</div>
                <h3>Categories</h3>
                <p>Create and manage content categories</p>
                <a href="categories.php" class="btn btn-warning">Manage Categories</a>
            </div>
            
            <div class="admin-card">
                <div class="icon">⚙️</div>
                <h3>Settings</h3>
                <p>Configure system settings and preferences</p>
                <a href="settings.php" class="btn btn-secondary">System Settings</a>
            </div>
            
            <div class="admin-card">
                <div class="icon">🔧</div>
                <h3>System Tools</h3>
                <p>Database setup, diagnostics, and maintenance tools</p>
                <a href="diagnostics.php" class="btn btn-danger">Run Diagnostics</a>
            </div>
        </div>
        
        <div class="tools-section">
            <h2>🛠️ System Tools & Utilities</h2>
            <div class="tools-grid">
                <a href="setup-database.php" class="tool-link">
                    <strong>Database Setup</strong><br>
                    <small>Initialize database tables</small>
                </a>
                <a href="diagnostics.php" class="tool-link">
                    <strong>System Diagnostics</strong><br>
                    <small>Check system health</small>
                </a>
                <a href="test-database.php" class="tool-link">
                    <strong>Database Test</strong><br>
                    <small>Test database connectivity</small>
                </a>
                <a href="../" class="tool-link">
                    <strong>View Website</strong><br>
                    <small>Open public website</small>
                </a>
                <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                <a href="dashboard.php?logout=1" class="tool-link" style="border-color: #dc3545; color: #dc3545;">
                    <strong>Logout</strong><br>
                    <small>End admin session</small>
                </a>
                <?php else: ?>
                <a href="dashboard.php" class="tool-link" style="border-color: #28a745; color: #28a745;">
                    <strong>Login</strong><br>
                    <small>Admin login</small>
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="margin-top: 30px; text-align: center; color: #666; font-size: 0.9em;">
            <p>E-Paper CMS v2.0 | Admin Panel | 
            <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                Logged in as: <?php echo htmlspecialchars($_SESSION['admin_user'] ?? 'Admin'); ?>
            <?php else: ?>
                <a href="dashboard.php">Please login to access admin functions</a>
            <?php endif; ?>
            </p>
        </div>
    </div>
</body>
</html>
