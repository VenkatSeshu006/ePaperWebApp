<?php
/**
 * Settings Page
 * System configuration for E-Paper CMS
 */

session_start();

// Include configuration first
require_once '../config/config.php';

// Simple authentication check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$messageType = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // This is a simplified settings implementation
        // In a real system, you'd save these to a database
        $settings = [
            'site_title' => filter_var($_POST['site_title'], FILTER_SANITIZE_STRING),
            'site_description' => filter_var($_POST['site_description'], FILTER_SANITIZE_STRING),
            'items_per_page' => filter_var($_POST['items_per_page'], FILTER_VALIDATE_INT),
            'enable_analytics' => isset($_POST['enable_analytics']),
            'enable_downloads' => isset($_POST['enable_downloads']),
            'max_file_size' => filter_var($_POST['max_file_size'], FILTER_VALIDATE_INT)
        ];
        
        // Simple validation
        if (!$settings['site_title']) {
            throw new Exception('Site title is required');
        }
        
        if ($settings['items_per_page'] < 1 || $settings['items_per_page'] > 100) {
            throw new Exception('Items per page must be between 1 and 100');
        }
        
        if ($settings['max_file_size'] < 1 || $settings['max_file_size'] > 100) {
            throw new Exception('Max file size must be between 1 and 100 MB');
        }
        
        // In a real implementation, save to database or config file
        $message = 'Settings updated successfully!';
        $messageType = 'success';
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Default settings (in a real system, load from database)
$settings = [
    'site_title' => 'Digital E-Paper',
    'site_description' => 'Your trusted source for digital news',
    'items_per_page' => 10,
    'enable_analytics' => true,
    'enable_downloads' => true,
    'max_file_size' => 50
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - E-Paper CMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .settings-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
        }
        
        .settings-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .form-check input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin: 0;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .header-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .help-text {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.25rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        @media (max-width: 768px) {
            .settings-container {
                padding: 1rem;
            }
            
            .header-nav {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="settings-container">
            <div class="header-nav">
                <h1><i class="fas fa-cog"></i> System Settings</h1>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <!-- General Settings -->
                <div class="settings-section">
                    <h2 class="section-title"><i class="fas fa-globe"></i> General Settings</h2>
                    
                    <div class="form-group">
                        <label for="site_title">Site Title</label>
                        <input type="text" id="site_title" name="site_title" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['site_title']); ?>" required>
                        <div class="help-text">This appears in the browser title and header</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_description">Site Description</label>
                        <textarea id="site_description" name="site_description" class="form-control" rows="3"
                                  placeholder="Brief description of your e-paper site..."><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                        <div class="help-text">Used for SEO and social media sharing</div>
                    </div>
                </div>
                
                <!-- Display Settings -->
                <div class="settings-section">
                    <h2 class="section-title"><i class="fas fa-eye"></i> Display Settings</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="items_per_page">Items Per Page</label>
                            <input type="number" id="items_per_page" name="items_per_page" class="form-control" 
                                   value="<?php echo $settings['items_per_page']; ?>" min="1" max="100" required>
                            <div class="help-text">Number of editions to show per page</div>
                        </div>
                    </div>
                </div>
                
                <!-- Feature Settings -->
                <div class="settings-section">
                    <h2 class="section-title"><i class="fas fa-toggle-on"></i> Features</h2>
                    
                    <div class="form-check">
                        <input type="checkbox" id="enable_analytics" name="enable_analytics" 
                               <?php echo $settings['enable_analytics'] ? 'checked' : ''; ?>>
                        <label for="enable_analytics">Enable Analytics Tracking</label>
                    </div>
                    <div class="help-text" style="margin-left: 1.5rem; margin-bottom: 1rem;">
                        Track page views, downloads, and user engagement
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" id="enable_downloads" name="enable_downloads" 
                               <?php echo $settings['enable_downloads'] ? 'checked' : ''; ?>>
                        <label for="enable_downloads">Enable PDF Downloads</label>
                    </div>
                    <div class="help-text" style="margin-left: 1.5rem; margin-bottom: 1rem;">
                        Allow users to download full PDF editions
                    </div>
                </div>
                
                <!-- Upload Settings -->
                <div class="settings-section">
                    <h2 class="section-title"><i class="fas fa-upload"></i> Upload Settings</h2>
                    
                    <div class="form-group">
                        <label for="max_file_size">Maximum File Size (MB)</label>
                        <input type="number" id="max_file_size" name="max_file_size" class="form-control" 
                               value="<?php echo $settings['max_file_size']; ?>" min="1" max="100" required>
                        <div class="help-text">Maximum size for uploaded PDF files</div>
                    </div>
                </div>
                
                <!-- System Information -->
                <div class="settings-section">
                    <h2 class="section-title"><i class="fas fa-info-circle"></i> System Information</h2>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div>
                            <strong>PHP Version:</strong><br>
                            <span style="color: #666;"><?php echo PHP_VERSION; ?></span>
                        </div>
                        <div>
                            <strong>Server Software:</strong><br>
                            <span style="color: #666;"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
                        </div>
                        <div>
                            <strong>Upload Max Size:</strong><br>
                            <span style="color: #666;"><?php echo ini_get('upload_max_filesize'); ?></span>
                        </div>
                        <div>
                            <strong>Memory Limit:</strong><br>
                            <span style="color: #666;"><?php echo ini_get('memory_limit'); ?></span>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
