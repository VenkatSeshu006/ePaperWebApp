<?php
/**
 * Admin Edit Edition Interface
 * Edit edition details
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

// Check if classes exist
if (file_exists('../classes/Edition.php')) {
    require_once '../classes/Edition.php';
} else {
    die('Required class files not found. Please check your installation.');
}

// Page configuration
$pageTitle = 'Edit Edition';
$pageSubtitle = 'Edit edition details';

$message = '';
$messageType = '';
$edition = null;

// Get edition ID
$editionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$editionId) {
    header('Location: manage_editions.php');
    exit;
}

// Load edition data
try {
    $editionModel = new Edition();
    $edition = $editionModel->getByIdAdmin($editionId);
    
    if (!$edition) {
        throw new Exception('Edition not found');
    }
} catch (Exception $e) {
    $message = $e->getMessage();
    $messageType = 'danger';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $edition) {
    try {
        $updateData = [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'status' => $_POST['status'] ?? 'draft'
        ];
        
        // Add date if provided
        if (!empty($_POST['publication_date'])) {
            $updateData['date'] = $_POST['publication_date'];
        }
        
        if (empty($updateData['title'])) {
            throw new Exception('Title is required');
        }
        
        $result = $editionModel->update($editionId, $updateData);
        
        if ($result) {
            $message = 'Edition updated successfully!';
            $messageType = 'success';
            
            // Reload edition data
            $edition = $editionModel->getByIdAdmin($editionId);
        } else {
            throw new Exception('Failed to update edition');
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// Set alert message for layout
if ($message) {
    $alertMessage = $message;
    $alertType = $messageType;
}

// Include the admin layout
require_once 'includes/admin_layout.php';
?>

<!-- Edit Edition Content -->
<div class="row">
    <div class="col-12">
        <div class="admin-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-edit"></i>
                    Edit Edition
                </h5>
            </div>
            <div class="card-body">
                <?php if ($edition): ?>
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="title" class="form-label">
                                    <i class="fas fa-heading"></i>
                                    Title <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="title" 
                                       name="title" 
                                       value="<?php echo htmlspecialchars($edition['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                       required>
                                <div class="invalid-feedback">
                                    Please provide a valid title.
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">
                                    <i class="fas fa-align-left"></i>
                                    Description
                                </label>
                                <textarea class="form-control" 
                                          id="description" 
                                          name="description" 
                                          rows="3"><?php echo htmlspecialchars($edition['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="publication_date" class="form-label">
                                            <i class="fas fa-calendar"></i>
                                            Publication Date
                                        </label>
                                        <input type="date" 
                                               class="form-control" 
                                               id="publication_date" 
                                               name="publication_date"
                                               value="<?php echo htmlspecialchars($edition['date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">
                                            <i class="fas fa-flag"></i>
                                            Status
                                        </label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="draft" <?php echo ($edition['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                            <option value="published" <?php echo ($edition['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6 class="mb-0">Edition Info</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <small class="text-muted">Created:</small><br>
                                        <span><?php echo date('M j, Y g:i A', strtotime($edition['created_at'] ?? '')); ?></span>
                                    </div>
                                    
                                    <?php if (!empty($edition['updated_at']) && $edition['updated_at'] !== $edition['created_at']): ?>
                                    <div class="mb-2">
                                        <small class="text-muted">Last Updated:</small><br>
                                        <span><?php echo date('M j, Y g:i A', strtotime($edition['updated_at'])); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-2">
                                        <small class="text-muted">Views:</small><br>
                                        <span><?php echo number_format($edition['views'] ?? 0); ?></span>
                                    </div>
                                    
                                    <?php if (!empty($edition['pdf_path'])): ?>
                                    <div class="mb-2">
                                        <small class="text-muted">PDF File:</small><br>
                                        <a href="../<?php echo htmlspecialchars($edition['pdf_path'], ENT_QUOTES, 'UTF-8'); ?>" 
                                           target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-file-pdf"></i> View PDF
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="mt-3">
                                        <a href="../view-edition.php?id=<?php echo $edition['id']; ?>" 
                                           target="_blank" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-eye"></i> Preview
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-admin-primary">
                                    <i class="fas fa-save"></i>
                                    Update Edition
                                </button>
                                <a href="manage_editions.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i>
                                    Back to Editions
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
                
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h4>Edition Not Found</h4>
                    <p class="text-muted">The requested edition could not be found.</p>
                    <a href="manage_editions.php" class="btn btn-admin-primary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Editions
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Form Validation Script -->
<script>
// Bootstrap form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>
