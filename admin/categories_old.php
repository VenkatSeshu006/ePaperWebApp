<?php
/**
 * Categories Management
 * Manage content categories for the E-Paper CMS
 */

session_start();

// Include configuration first
require_once '../config/config.php';

// Simple authentication check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: dashboard.php');
    exit;
}

require_once '../includes/database.php';

// Check if classes exist
if (file_exists('../classes/Category.php')) {
    require_once '../classes/Category.php';
} else {
    die('Required class files not found. Please check your installation.');
}

$message = '';
$messageType = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        if (class_exists('Category')) {
            $categoryModel = new Category();
            
            switch ($action) {
                case 'create':
                    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
                    $slug = filter_var($_POST['slug'], FILTER_SANITIZE_STRING);
                    $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
                    $color = filter_var($_POST['color'], FILTER_SANITIZE_STRING);
                    $icon = filter_var($_POST['icon'], FILTER_SANITIZE_STRING);
                    
                    if (!$name) {
                        throw new Exception('Category name is required');
                    }
                    
                    // Auto-generate slug if not provided
                    if (!$slug) {
                        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
                    }
                    
                    $categoryData = [
                        'name' => $name,
                        'slug' => $slug,
                        'description' => $description,
                        'color' => $color ?: '#007bff',
                        'icon' => $icon ?: 'fas fa-folder',
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    if ($categoryModel->create($categoryData)) {
                        $message = 'Category created successfully';
                        $messageType = 'success';
                    } else {
                        throw new Exception('Failed to create category');
                    }
                    break;
                    
                case 'update':
                    $id = filter_var($_POST['category_id'], FILTER_VALIDATE_INT);
                    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
                    $slug = filter_var($_POST['slug'], FILTER_SANITIZE_STRING);
                    $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
                    $color = filter_var($_POST['color'], FILTER_SANITIZE_STRING);
                    $icon = filter_var($_POST['icon'], FILTER_SANITIZE_STRING);
                    
                    if (!$id || !$name) {
                        throw new Exception('Category ID and name are required');
                    }
                    
                    $categoryData = [
                        'name' => $name,
                        'slug' => $slug,
                        'description' => $description,
                        'color' => $color,
                        'icon' => $icon
                    ];
                    
                    if ($categoryModel->update($id, $categoryData)) {
                        $message = 'Category updated successfully';
                        $messageType = 'success';
                    } else {
                        throw new Exception('Failed to update category');
                    }
                    break;
                    
                case 'delete':
                    $id = filter_var($_POST['category_id'], FILTER_VALIDATE_INT);
                    
                    if (!$id) {
                        throw new Exception('Category ID is required');
                    }
                    
                    if ($categoryModel->delete($id)) {
                        $message = 'Category deleted successfully';
                        $messageType = 'success';
                    } else {
                        throw new Exception('Failed to delete category');
                    }
                    break;
                    
                default:
                    throw new Exception('Invalid action');
            }
        } else {
            throw new Exception('Category class not found');
        }
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get categories
$categories = [];
if (class_exists('Category')) {
    try {
        $categoryModel = new Category();
        $categories = $categoryModel->getAll();
    } catch (Exception $e) {
        error_log('Error fetching categories: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - E-Paper CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #0056b3 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .category-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .category-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }
        
        .form-floating .form-control {
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .form-floating .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        
        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            display: inline-block;
        }
        
        .icon-preview {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            margin-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .icon-preview.has-icon {
            border-color: var(--primary-color);
            background: rgba(13, 110, 253, 0.05);
        }
        
        .category-item {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .category-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .category-meta {
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .btn-group-sm > .btn, .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .alert {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
            border: 2px dashed #dee2e6;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 1.5rem;
        }
    </style>
</head>
<body>
    <!-- Admin Navigation -->
    <?php include 'includes/admin_nav.php'; ?>

    <!-- Main Header -->
    <div class="main-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0"><i class="fas fa-tags me-3"></i>Category Management</h1>
                    <p class="mb-0 mt-2 opacity-75">Organize your content with categories</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="stats-card d-inline-block">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-folder-open fa-2x me-3"></i>
                            <div>
                                <div class="h4 mb-0"><?php echo count($categories); ?></div>
                                <small>Total Categories</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $messageType === 'error' ? 'exclamation-triangle' : 'check-circle'; ?> me-2"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Category Form -->
            <div class="col-lg-4">
                <div class="card category-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-plus-circle me-2"></i>
                            <span id="formTitle">Add New Category</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="categoryForm">
                            <input type="hidden" name="action" value="create" id="formAction">
                            <input type="hidden" name="category_id" id="categoryId">
                            
                            <div class="form-floating mb-3">
                                <input type="text" id="name" name="name" class="form-control" 
                                       placeholder="Category Name" required>
                                <label for="name"><i class="fas fa-tag me-2"></i>Category Name</label>
                            </div>
                            
                            <div class="form-floating mb-3">
                                <input type="text" id="slug" name="slug" class="form-control" 
                                       placeholder="URL Slug">
                                <label for="slug"><i class="fas fa-link me-2"></i>URL Slug</label>
                                <div class="form-text">Leave empty to auto-generate from name</div>
                            </div>
                            
                            <div class="form-floating mb-3">
                                <textarea id="description" name="description" class="form-control" 
                                          placeholder="Description" style="height: 100px"></textarea>
                                <label for="description"><i class="fas fa-align-left me-2"></i>Description</label>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-palette me-2"></i>Color</label>
                                    <div class="d-flex align-items-center">
                                        <input type="color" id="color" name="color" class="form-control form-control-color me-2" 
                                               value="#0d6efd" style="width: 50px;">
                                        <div class="color-preview" id="colorPreview" style="background-color: #0d6efd;"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-icons me-2"></i>Icon Class</label>
                                    <input type="text" id="icon" name="icon" class="form-control" 
                                           placeholder="fas fa-folder" value="fas fa-folder">
                                </div>
                            </div>
                            
                            <div class="icon-preview" id="iconPreview">
                                <i class="fas fa-folder fa-2x text-primary mb-2"></i>
                                <div class="small text-muted">Icon Preview</div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                                <button type="button" class="btn btn-outline-secondary" onclick="resetForm()" 
                                        id="cancelBtn" style="display: none;">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </button>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save me-1"></i>Create Category
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Categories List -->
            <div class="col-lg-8">
                <div class="card category-card">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list me-2"></i>Categories
                            </h5>
                            <span class="badge bg-primary"><?php echo count($categories); ?> Total</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $index => $category): ?>
                            <div class="category-item border-bottom">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <div class="color-preview me-3" style="background-color: <?php echo htmlspecialchars($category['color']); ?>"></div>
                                        <i class="<?php echo htmlspecialchars($category['icon']); ?> fa-lg me-3" 
                                           style="color: <?php echo htmlspecialchars($category['color']); ?>"></i>
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($category['name']); ?></h6>
                                            <div class="category-meta">
                                                <small class="text-muted">
                                                    <i class="fas fa-link me-1"></i><?php echo htmlspecialchars($category['slug']); ?>
                                                    <?php if (!empty($category['description'])): ?>
                                                        <span class="mx-2">|</span>
                                                        <?php echo htmlspecialchars(substr($category['description'], 0, 50)) . (strlen($category['description']) > 50 ? '...' : ''); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                                onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)"
                                                data-bs-toggle="tooltip" title="Edit Category">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')"
                                                data-bs-toggle="tooltip" title="Delete Category">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">No Categories Yet</h4>
                            <p class="text-muted">Create your first category to start organizing your content.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the category <strong id="deleteCategoryName"></strong>?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        This action cannot be undone and will remove the category from all associated editions.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="deleteForm" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="category_id" id="deleteCategoryId">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Delete Category
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Color preview update
        document.getElementById('color').addEventListener('input', function() {
            document.getElementById('colorPreview').style.backgroundColor = this.value;
        });
        
        // Icon preview update
        document.getElementById('icon').addEventListener('input', function() {
            const iconClass = this.value || 'fas fa-folder';
            const preview = document.getElementById('iconPreview');
            const isValid = iconClass.includes('fa-');
            
            if (isValid) {
                preview.innerHTML = `<i class="${iconClass} fa-2x text-primary mb-2"></i><div class="small text-muted">Icon Preview</div>`;
                preview.classList.add('has-icon');
            } else {
                preview.innerHTML = `<i class="fas fa-question fa-2x text-muted mb-2"></i><div class="small text-danger">Invalid icon class</div>`;
                preview.classList.remove('has-icon');
            }
        });
        
        // Auto-generate slug from name
        document.getElementById('name').addEventListener('input', function() {
            if (document.getElementById('formAction').value === 'create') {
                const slug = this.value.toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                document.getElementById('slug').value = slug;
            }
        });
        
        // Edit category function
        function editCategory(category) {
            document.getElementById('formAction').value = 'update';
            document.getElementById('categoryId').value = category.id;
            document.getElementById('name').value = category.name;
            document.getElementById('slug').value = category.slug;
            document.getElementById('description').value = category.description || '';
            document.getElementById('color').value = category.color;
            document.getElementById('icon').value = category.icon;
            
            // Update previews
            document.getElementById('colorPreview').style.backgroundColor = category.color;
            const preview = document.getElementById('iconPreview');
            preview.innerHTML = `<i class="${category.icon} fa-2x text-primary mb-2"></i><div class="small text-muted">Icon Preview</div>`;
            preview.classList.add('has-icon');
            
            // Update form UI
            document.getElementById('formTitle').textContent = 'Edit Category';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Update Category';
            document.getElementById('cancelBtn').style.display = 'inline-block';
            
            // Scroll to form
            document.getElementById('categoryForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        // Reset form function
        function resetForm() {
            document.getElementById('categoryForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('categoryId').value = '';
            document.getElementById('formTitle').textContent = 'Add New Category';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Create Category';
            document.getElementById('cancelBtn').style.display = 'none';
            
            // Reset previews
            document.getElementById('colorPreview').style.backgroundColor = '#0d6efd';
            const preview = document.getElementById('iconPreview');
            preview.innerHTML = '<i class="fas fa-folder fa-2x text-primary mb-2"></i><div class="small text-muted">Icon Preview</div>';
            preview.classList.add('has-icon');
        }
        
        // Delete category function
        function deleteCategory(id, name) {
            document.getElementById('deleteCategoryId').value = id;
            document.getElementById('deleteCategoryName').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(function(alert) {
                const alertInstance = new bootstrap.Alert(alert);
                alertInstance.close();
            });
        }, 5000);
    </script>
</body>
</html>
