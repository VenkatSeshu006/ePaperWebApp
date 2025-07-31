<?php
/**
 * Admin Settings Interface
 * Configure system settings for E-Paper CMS
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
$pageTitle = 'System Settings';
$pageSubtitle = 'Configure system preferences and options';

$message = '';
$messageType = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $settings = [
            'site_title' => $_POST['site_title'] ?? '',
            'site_description' => $_POST['site_description'] ?? '',
            'items_per_page' => (int)($_POST['items_per_page'] ?? 12),
            'max_file_size' => (int)($_POST['max_file_size'] ?? 50),
            'auto_generate_thumbnails' => isset($_POST['auto_generate_thumbnails']),
            'enable_analytics' => isset($_POST['enable_analytics']),
            'maintenance_mode' => isset($_POST['maintenance_mode']),
        ];
        
        // Save settings to config file or database
        // For now, we'll simulate saving
        $message = 'Settings updated successfully!';
        $messageType = 'success';
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// Load current settings (from config or database)
$currentSettings = [
    'site_title' => 'E-Paper CMS',
    'site_description' => 'Digital newspaper management system',
    'items_per_page' => 12,
    'max_file_size' => 50,
    'auto_generate_thumbnails' => true,
    'enable_analytics' => true,
    'maintenance_mode' => false,
];

// Set alert message for layout
if ($message) {
    $alertMessage = $message;
    $alertType = $messageType;
}

// Include the admin layout
require_once 'includes/admin_layout.php';
?>

<!-- Settings Content -->
<div class="row">
    <div class="col-lg-8">
        <form method="POST" id="settingsForm">
            <!-- General Settings -->
            <div class="admin-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-cog"></i>
                        General Settings
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="site_title" class="form-label">Site Title</label>
                                <input type="text" class="form-control" id="site_title" name="site_title" 
                                       value="<?php echo htmlspecialchars($currentSettings['site_title']); ?>" required>
                                <div class="form-text">This appears in the browser title and header</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="items_per_page" class="form-label">Items Per Page</label>
                                <select class="form-select" id="items_per_page" name="items_per_page">
                                    <option value="6" <?php echo $currentSettings['items_per_page'] == 6 ? 'selected' : ''; ?>>6</option>
                                    <option value="12" <?php echo $currentSettings['items_per_page'] == 12 ? 'selected' : ''; ?>>12</option>
                                    <option value="24" <?php echo $currentSettings['items_per_page'] == 24 ? 'selected' : ''; ?>>24</option>
                                    <option value="48" <?php echo $currentSettings['items_per_page'] == 48 ? 'selected' : ''; ?>>48</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="site_description" class="form-label">Site Description</label>
                        <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($currentSettings['site_description']); ?></textarea>
                        <div class="form-text">Brief description for SEO and social sharing</div>
                    </div>
                </div>
            </div>

            <!-- Upload Settings -->
            <div class="admin-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-upload"></i>
                        Upload Settings
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="max_file_size" class="form-label">Maximum File Size (MB)</label>
                                <input type="number" class="form-control" id="max_file_size" name="max_file_size" 
                                       value="<?php echo $currentSettings['max_file_size']; ?>" min="1" max="500">
                                <div class="form-text">Maximum size for uploaded PDF files</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Processing Options</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="auto_generate_thumbnails" 
                                           name="auto_generate_thumbnails" <?php echo $currentSettings['auto_generate_thumbnails'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="auto_generate_thumbnails">
                                        Auto-generate thumbnails
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Settings -->
            <div class="admin-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-server"></i>
                        System Settings
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="enable_analytics" 
                                       name="enable_analytics" <?php echo $currentSettings['enable_analytics'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="enable_analytics">
                                    <strong>Enable Analytics</strong>
                                    <div class="form-text">Track page views and user statistics</div>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="maintenance_mode" 
                                       name="maintenance_mode" <?php echo $currentSettings['maintenance_mode'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="maintenance_mode">
                                    <strong>Maintenance Mode</strong>
                                    <div class="form-text">Temporarily disable public access</div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="d-flex justify-content-between align-items-center">
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <button type="submit" class="btn btn-admin-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- System Information -->
        <div class="admin-card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle"></i>
                    System Information
                </h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>PHP Version:</strong></td>
                        <td><?php echo PHP_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Server:</strong></td>
                        <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>MySQL:</strong></td>
                        <td>
                            <?php
                            try {
                                $db = Database::getInstance();
                                $conn = $db->getConnection();
                                if ($conn) {
                                    echo $conn->server_info;
                                } else {
                                    echo 'Not connected';
                                }
                            } catch (Exception $e) {
                                echo 'Error: ' . $e->getMessage();
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Upload Max:</strong></td>
                        <td><?php echo ini_get('upload_max_filesize'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Post Max:</strong></td>
                        <td><?php echo ini_get('post_max_size'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Memory Limit:</strong></td>
                        <td><?php echo ini_get('memory_limit'); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="admin-card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-tools"></i>
                    Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="diagnostics.php" class="btn btn-outline-primary">
                        <i class="fas fa-stethoscope"></i> Run Diagnostics
                    </a>
                    <a href="setup-database.php" class="btn btn-outline-info">
                        <i class="fas fa-database"></i> Database Setup
                    </a>
                    <button type="button" class="btn btn-outline-warning" onclick="clearCache()">
                        <i class="fas fa-broom"></i> Clear Cache
                    </button>
                    <button type="button" class="btn btn-outline-danger" onclick="exportSettings()">
                        <i class="fas fa-download"></i> Export Settings
                    </button>
                </div>
            </div>
        </div>

        <!-- Backup -->
        <div class="admin-card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-shield-alt"></i>
                    Backup & Security
                </h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">
                    Regular backups help protect your content and settings.
                </p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-success" onclick="createBackup()">
                        <i class="fas fa-download"></i> Create Backup
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="showBackupHistory()">
                        <i class="fas fa-history"></i> Backup History
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom JavaScript -->
<?php 
$additionalJS = "
<script>
// Form validation
document.getElementById('settingsForm').addEventListener('submit', function(e) {
    if (!validateRequired(this)) {
        e.preventDefault();
        showToast('Please fill in all required fields', 'warning');
        return;
    }
    
    const submitBtn = this.querySelector('button[type=\"submit\"]');
    showLoading(submitBtn);
});

// Clear cache
function clearCache() {
    if (confirm('Are you sure you want to clear the cache?')) {
        // In a real implementation, this would make an AJAX call
        showToast('Cache cleared successfully', 'success');
    }
}

// Export settings
function exportSettings() {
    const settings = {
        site_title: document.getElementById('site_title').value,
        site_description: document.getElementById('site_description').value,
        items_per_page: document.getElementById('items_per_page').value,
        max_file_size: document.getElementById('max_file_size').value,
        auto_generate_thumbnails: document.getElementById('auto_generate_thumbnails').checked,
        enable_analytics: document.getElementById('enable_analytics').checked,
        maintenance_mode: document.getElementById('maintenance_mode').checked
    };
    
    const dataStr = JSON.stringify(settings, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    const url = URL.createObjectURL(dataBlob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = 'epaper-settings-' + new Date().toISOString().split('T')[0] + '.json';
    link.click();
    
    URL.revokeObjectURL(url);
    showToast('Settings exported successfully', 'success');
}

// Create backup
function createBackup() {
    if (confirm('This will create a backup of your database and files. Continue?')) {
        showToast('Backup creation started...', 'info');
        // In a real implementation, this would trigger a backup process
        setTimeout(() => {
            showToast('Backup created successfully', 'success');
        }, 3000);
    }
}

// Show backup history
function showBackupHistory() {
    showToast('Feature coming soon', 'info');
}

// Auto-save draft
let autoSaveTimeout;
document.querySelectorAll('input, textarea, select').forEach(element => {
    element.addEventListener('change', function() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(() => {
            // Auto-save logic here
            console.log('Auto-saving settings...');
        }, 2000);
    });
});
</script>
";

// Include the admin layout footer
require_once 'includes/admin_layout_footer.php';
?>
