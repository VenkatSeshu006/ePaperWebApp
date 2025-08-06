<?php
/**
 * Watermark Settings Management
 * Admin interface for uploading and managing publisher watermarks
 */

session_start();
define('ADMIN_PAGE', true);

// Include configuration first
require_once '../config.php';

// Simple authentication check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ./dashboard.php');
    exit;
}

require_once '../includes/database.php';

$message = '';
$messageType = '';

// Handle watermark upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['watermark_logo'])) {
    try {
        $file = $_FILES['watermark_logo'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $file['error']);
        }
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Only JPG, PNG, and GIF images are allowed for watermarks');
        }
        
        // Validate file size (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            throw new Exception('Watermark file must be less than 2MB');
        }
        
        // Create watermarks directory
        $watermarkDir = '.uploads/watermarks/';
        if (!is_dir($watermarkDir)) {
            mkdir($watermarkDir, 0755, true);
        }
        
        // Generate filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'publisher_logo.' . $extension;
        $filepath = $watermarkDir . $filename;
        
        // Remove old watermark if exists
        $oldFiles = glob($watermarkDir . 'publisher_logo.*');
        foreach ($oldFiles as $oldFile) {
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Save watermark settings to database
            $conn = getConnection();
            
            // Delete old watermark settings
            $conn->query("DELETE FROM settings WHERE setting_key LIKE 'watermark_%'");
            
            // Insert new watermark settings
            $settings = [
                'watermark_enabled' => '1',
                'watermark_logo_path' => str_replace('../', '', $filepath),
                'watermark_position' => $_POST['watermark_position'] ?? 'top-center',
                'watermark_opacity' => $_POST['watermark_opacity'] ?? '80',
                'watermark_size' => $_POST['watermark_size'] ?? 'medium',
                'watermark_margin' => $_POST['watermark_margin'] ?? '20'
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
                $stmt->execute([$key, $value]);
            }
            
            $message = 'Watermark uploaded and configured successfully!';
            $messageType = 'success';
        } else {
            throw new Exception('Failed to save watermark file');
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// Handle watermark disable
if (isset($_POST['disable_watermark'])) {
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("UPDATE settings SET setting_value = '0' WHERE setting_key = 'watermark_enabled'");
        $stmt->execute();
        
        $message = 'Watermark disabled successfully!';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// Get current watermark settings
$conn = getConnection();
$watermarkSettings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'watermark_%'");
while ($row = $result->fetch()) {
    $watermarkSettings[$row['setting_key']] = $row['setting_value'];
}

$pageTitle = 'Watermark Settings';
$pageSubtitle = 'Manage publisher watermark for clips';

// Set alert message for layout
if ($message) {
    $alertMessage = $message;
    $alertType = $messageType;
}

// Include the admin layout
require_once 'includes/admin_layout.php';
?>

<!-- Watermark Settings -->
<div class="row justify-content-center">
    <div class="col-lg-10">
        
        <!-- Current Watermark Status -->
        <div class="admin-card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-image"></i>
                    Current Watermark Status
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($watermarkSettings['watermark_enabled']) && $watermarkSettings['watermark_enabled'] === '1'): ?>
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-success me-3">
                                    <i class="fas fa-check"></i> Active
                                </span>
                                <div>
                                    <h6 class="mb-1">Watermark is enabled</h6>
                                    <small class="text-muted">
                                        All downloaded and shared clips will include the publisher watermark
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php if (!empty($watermarkSettings['watermark_logo_path']) && file_exists($watermarkSettings['watermark_logo_path'])): ?>
                                <div class="watermark-preview mb-2">
                                    <img src="../<?php echo htmlspecialchars($watermarkSettings['watermark_logo_path']); ?>" 
                                         alt="Current Watermark" 
                                         style="max-height: 60px; max-width: 150px; border: 1px solid #dee2e6; border-radius: 4px;">
                                </div>
                            <?php endif; ?>
                            <form method="POST" style="display: inline;">
                                <button type="submit" name="disable_watermark" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-times"></i> Disable
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3">
                        <span class="badge bg-warning me-3">
                            <i class="fas fa-exclamation-triangle"></i> Inactive
                        </span>
                        <span class="text-muted">No watermark is currently active</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upload New Watermark -->
        <div class="admin-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-upload"></i>
                    Upload Publisher Watermark
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="watermarkForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="watermark_logo" class="form-label">
                                    <i class="fas fa-image"></i>
                                    Publisher Logo *
                                </label>
                                <input type="file" class="form-control" id="watermark_logo" name="watermark_logo" 
                                       accept="image/*" required>
                                <div class="form-text">
                                    Upload your publisher logo (JPG, PNG, GIF - Max 2MB)
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="watermark_position" class="form-label">
                                    <i class="fas fa-crosshairs"></i>
                                    Position
                                </label>
                                <select class="form-select" id="watermark_position" name="watermark_position">
                                    <option value="top-center" <?php echo (($watermarkSettings['watermark_position'] ?? '') === 'top-center') ? 'selected' : ''; ?>>
                                        Top Center (Recommended)
                                    </option>
                                    <option value="top-left" <?php echo (($watermarkSettings['watermark_position'] ?? '') === 'top-left') ? 'selected' : ''; ?>>
                                        Top Left
                                    </option>
                                    <option value="top-right" <?php echo (($watermarkSettings['watermark_position'] ?? '') === 'top-right') ? 'selected' : ''; ?>>
                                        Top Right
                                    </option>
                                    <option value="bottom-center" <?php echo (($watermarkSettings['watermark_position'] ?? '') === 'bottom-center') ? 'selected' : ''; ?>>
                                        Bottom Center
                                    </option>
                                    <option value="bottom-left" <?php echo (($watermarkSettings['watermark_position'] ?? '') === 'bottom-left') ? 'selected' : ''; ?>>
                                        Bottom Left
                                    </option>
                                    <option value="bottom-right" <?php echo (($watermarkSettings['watermark_position'] ?? '') === 'bottom-right') ? 'selected' : ''; ?>>
                                        Bottom Right
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="watermark_size" class="form-label">
                                    <i class="fas fa-expand-arrows-alt"></i>
                                    Size
                                </label>
                                <select class="form-select" id="watermark_size" name="watermark_size">
                                    <option value="small" <?php echo (($watermarkSettings['watermark_size'] ?? '') === 'small') ? 'selected' : ''; ?>>
                                        Small (100px)
                                    </option>
                                    <option value="medium" <?php echo (($watermarkSettings['watermark_size'] ?? 'medium') === 'medium') ? 'selected' : ''; ?>>
                                        Medium (150px)
                                    </option>
                                    <option value="large" <?php echo (($watermarkSettings['watermark_size'] ?? '') === 'large') ? 'selected' : ''; ?>>
                                        Large (200px)
                                    </option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="watermark_opacity" class="form-label">
                                    <i class="fas fa-adjust"></i>
                                    Opacity
                                </label>
                                <select class="form-select" id="watermark_opacity" name="watermark_opacity">
                                    <option value="60" <?php echo (($watermarkSettings['watermark_opacity'] ?? '') === '60') ? 'selected' : ''; ?>>
                                        60% (Light)
                                    </option>
                                    <option value="80" <?php echo (($watermarkSettings['watermark_opacity'] ?? '80') === '80') ? 'selected' : ''; ?>>
                                        80% (Recommended)
                                    </option>
                                    <option value="100" <?php echo (($watermarkSettings['watermark_opacity'] ?? '') === '100') ? 'selected' : ''; ?>>
                                        100% (Opaque)
                                    </option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="watermark_margin" class="form-label">
                                    <i class="fas fa-border-style"></i>
                                    Margin (px)
                                </label>
                                <input type="number" class="form-control" id="watermark_margin" name="watermark_margin" 
                                       value="<?php echo htmlspecialchars($watermarkSettings['watermark_margin'] ?? '20'); ?>"
                                       min="0" max="100">
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            <i class="fas fa-info-circle"></i>
                            Watermark will be applied to all clip downloads and shares
                        </div>
                        <div>
                            <a href="dashboard.php" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-arrow-left"></i>
                                Back to Dashboard
                            </a>
                            <button type="submit" class="btn btn-admin-primary">
                                <i class="fas fa-save"></i>
                                Save Watermark Settings
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Watermark Guidelines -->
        <div class="admin-card mt-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-lightbulb"></i>
                    Watermark Guidelines
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center mb-3">
                            <i class="fas fa-palette fa-2x text-primary mb-2"></i>
                            <h6>Logo Design</h6>
                            <p class="small text-muted">
                                Use a high-contrast logo with transparent background for best results.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center mb-3">
                            <i class="fas fa-compress-arrows-alt fa-2x text-success mb-2"></i>
                            <h6>Optimal Size</h6>
                            <p class="small text-muted">
                                Recommended: 300x100px or similar aspect ratio for clear visibility.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center mb-3">
                            <i class="fas fa-eye fa-2x text-info mb-2"></i>
                            <h6>Visibility</h6>
                            <p class="small text-muted">
                                Test with different content to ensure logo is always visible and readable.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom JavaScript -->
<?php 
$additionalJS = "
<script>
document.getElementById('watermark_logo').addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        if (!file.type.startsWith('image/')) {
            showToast('Please select an image file', 'danger');
            this.value = '';
            return;
        }
        
        if (file.size > 2 * 1024 * 1024) {
            showToast('File size must be less than 2MB', 'danger');
            this.value = '';
            return;
        }
        
        showToast('Logo selected: ' + file.name, 'success');
    }
});
</script>
";

// Include the admin layout footer
require_once 'includes/admin_layout_footer.php';
?>
