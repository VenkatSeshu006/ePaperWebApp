<?php
/**
 * Admin Categories Interface
 * Manage content categories for E-Paper CMS
 */

session_start();
define('ADMIN_PAGE', true);

// Include configuration first
require_once '../config.php';

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
$pageTitle = 'Categories';
$pageSubtitle = 'Manage content categories and tags';

$message = '';
$messageType = '';

// Debug: Log form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log("Categories POST request received");
    error_log("POST data: " . print_r($_POST, true));
    error_log("Request URI: " . $_SERVER['REQUEST_URI']);
    error_log("HTTP Referer: " . ($_SERVER['HTTP_REFERER'] ?? 'None'));
}

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        if (!$conn) {
            throw new Exception('Database connection failed');
        }
        
        $action = $_POST['action'] ?? '';
        error_log("Processing action: " . $action);
        
        switch ($action) {
            case 'create':
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $color = $_POST['color'] ?? '#007bff';
                
                error_log("Creating category - Name: $name, Description: $description, Color: $color");
                
                if (empty($name)) {
                    throw new Exception('Category name is required');
                }
                
                $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $name));
                error_log("Generated slug: $slug");
                
                $stmt = $conn->prepare("INSERT INTO categories (name, slug, description, color) VALUES (?, ?, ?, ?)");
                $result = $stmt->execute([$name, $slug, $description, $color]);
                
                if ($result) {
                    $newId = $conn->lastInsertId();
                    $message = 'Category created successfully! (ID: ' . $newId . ')';
                    $messageType = 'success';
                    error_log("Category created successfully with ID: $newId");
                } else {
                    throw new Exception('Failed to create category - database insert failed');
                }
                break;
                
            case 'update':
                $id = (int)($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $color = $_POST['color'] ?? '#007bff';
                
                error_log("Updating category - ID: $id, Name: $name, Description: $description, Color: $color");
                
                if ($id <= 0 || empty($name)) {
                    throw new Exception('Invalid category data - ID: ' . $id . ', Name: ' . $name);
                }
                
                $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $name));
                error_log("Generated slug for update: $slug");
                
                $stmt = $conn->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, color = ? WHERE id = ?");
                $result = $stmt->execute([$name, $slug, $description, $color, $id]);
                
                if ($result) {
                    $affectedRows = $stmt->rowCount();
                    $message = 'Category updated successfully! (Affected rows: ' . $affectedRows . ')';
                    $messageType = 'success';
                    error_log("Category updated successfully - affected rows: $affectedRows");
                } else {
                    throw new Exception('Failed to update category - database update failed');
                }
                break;
                
            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                
                error_log("Deleting category - ID: $id");
                
                if ($id <= 0) {
                    throw new Exception('Invalid category ID for deletion: ' . $id);
                }
                
                // Check if category has any associated editions first
                $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM edition_categories WHERE category_id = ?");
                $checkStmt->execute([$id]);
                $editionCount = $checkStmt->fetch()['count'] ?? 0;
                
                error_log("Category has $editionCount associated editions");
                
                $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
                $result = $stmt->execute([$id]);
                
                if ($result) {
                    $affectedRows = $stmt->rowCount();
                    if ($affectedRows > 0) {
                        $message = 'Category deleted successfully! (Had ' . $editionCount . ' associated editions)';
                        $messageType = 'success';
                        error_log("Category deleted successfully - affected rows: $affectedRows");
                    } else {
                        throw new Exception('Category not found or already deleted');
                    }
                } else {
                    throw new Exception('Failed to delete category - database delete failed');
                }
                break;
                
            default:
                error_log("Unknown action received: $action");
                throw new Exception('Unknown action: ' . $action);
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// Get categories
$categories = [];
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    if ($conn) {
        // Check if categories table exists, create if not
        $tableCheck = $conn->query("SHOW TABLES LIKE 'categories'");
        if ($tableCheck->rowCount() == 0) {
            $createTable = "
            CREATE TABLE categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                description TEXT,
                color VARCHAR(7) DEFAULT '#007bff',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $conn->exec($createTable);
        }
        
        $result = $conn->query("SELECT c.*, COUNT(ec.edition_id) as edition_count 
                               FROM categories c 
                               LEFT JOIN edition_categories ec ON c.id = ec.category_id 
                               GROUP BY c.id 
                               ORDER BY c.name");
        
        if ($result) {
            while ($row = $result->fetch()) {
                $categories[] = $row;
            }
        }
    }
} catch (Exception $e) {
    $message = 'Error loading categories: ' . $e->getMessage();
    $messageType = 'danger';
}

// Set alert message for layout
if ($message) {
    $alertMessage = $message;
    $alertType = $messageType;
}

// Include the admin layout
require_once 'includes/admin_layout.php';
?>

<!-- Categories Content -->
<div class="row">
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-tags"></i>
                    Categories (<?php echo count($categories); ?>)
                </h5>
                <button type="button" class="btn btn-admin-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                    <i class="fas fa-plus"></i>
                    Add Category
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($categories)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-tags fa-4x text-gray-300 mb-3"></i>
                        <h5 class="text-gray-500">No categories found</h5>
                        <p class="text-muted">Create your first category to organize content</p>
                        <button type="button" class="btn btn-admin-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                            <i class="fas fa-plus"></i> Create First Category
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Category</th>
                                    <th>Slug</th>
                                    <th>Description</th>
                                    <th>Editions</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="category-color me-2" 
                                                 style="width: 20px; height: 20px; background-color: <?php echo htmlspecialchars(($category['color'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>; border-radius: 4px;"></div>
                                            <strong><?php echo htmlspecialchars(($category['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars(($category['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <?php 
                                            $desc = htmlspecialchars($category['description'] ?? '', ENT_QUOTES, 'UTF-8');
                                            echo strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc;
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo number_format(($category['edition_count'] ?? '')); ?> editions
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)"
                                                    title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteCategory(<?php echo ($category['id'] ?? ''); ?>, '<?php echo htmlspecialchars(addslashes($category['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>')"
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
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

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Category Statistics -->
        <div class="admin-card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-chart-pie"></i>
                    Category Statistics
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($categories)): ?>
                    <?php foreach (array_slice($categories, 0, 5) as $category): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center">
                            <div class="category-color me-2" 
                                 style="width: 12px; height: 12px; background-color: <?php echo htmlspecialchars(($category['color'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>; border-radius: 2px;"></div>
                            <span class="small"><?php echo htmlspecialchars(($category['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <span class="badge bg-light text-dark"><?php echo ($category['edition_count'] ?? ''); ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted small">No categories to display statistics</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="admin-card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-bolt"></i>
                    Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="testAddCategory()">
                        <i class="fas fa-cog"></i> Test Modal
                    </button>
                    <button type="button" class="btn btn-outline-success" onclick="exportCategories()">
                        <i class="fas fa-file-export"></i> Export Categories
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryModalTitle">Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="categoryForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="categoryAction" value="create">
                    <input type="hidden" name="id" id="categoryId">
                    
                    <div class="mb-3">
                        <label for="categoryName" class="form-label">Name *</label>
                        <input type="text" class="form-control" id="categoryName" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="categoryDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="categoryDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="categoryColor" class="form-label">Color</label>
                        <div class="d-flex align-items-center">
                            <input type="color" class="form-control form-control-color me-2" id="categoryColor" name="color" value="#007bff">
                            <span class="small text-muted">Choose a color to represent this category</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-admin-primary" id="categorySaveBtn">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Category Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the category <strong id="deleteCategoryName"></strong>?</p>
                <p class="text-danger small">This action cannot be undone. All associations with editions will be removed.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteCategoryId">
                    <button type="submit" class="btn btn-danger">Delete Category</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Custom JavaScript -->
<?php 
$additionalJS = "
<script>
console.log('Categories page JavaScript loading...');

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - initializing categories functionality');
    
    // Check if Bootstrap is loaded
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap JavaScript not loaded!');
        alert('Error: Bootstrap JavaScript not loaded. Modal functionality will not work.');
        return;
    } else {
        console.log('Bootstrap loaded successfully, version:', bootstrap.Modal.VERSION || 'Unknown');
    }
    
    // Reset modal when hidden
    const categoryModal = document.getElementById('categoryModal');
    if (categoryModal) {
        console.log('Category modal found, setting up event listeners');
        
        categoryModal.addEventListener('hidden.bs.modal', function() {
            console.log('Modal hidden - resetting form');
            document.getElementById('categoryModalTitle').textContent = 'Add Category';
            document.getElementById('categoryAction').value = 'create';
            document.getElementById('categoryId').value = '';
            document.getElementById('categoryName').value = '';
            document.getElementById('categoryDescription').value = '';
            document.getElementById('categoryColor').value = '#007bff';
            document.getElementById('categorySaveBtn').textContent = 'Save Category';
        });
        
        categoryModal.addEventListener('shown.bs.modal', function() {
            console.log('Modal shown - focusing on name field');
            document.getElementById('categoryName').focus();
        });
    } else {
        console.error('Category modal not found!');
    }
    
    // Test modal buttons
    const addButtons = document.querySelectorAll('[data-bs-target=\"#categoryModal\"]');
    console.log('Found', addButtons.length, 'modal trigger buttons');
    
    addButtons.forEach(function(button, index) {
        button.addEventListener('click', function() {
            console.log('Modal trigger button', index, 'clicked');
        });
    });
});

// Test function to manually open modal with better error handling
function testAddCategory() {
    console.log('Testing category modal...');
    try {
        if (typeof bootstrap === 'undefined') {
            throw new Error('Bootstrap is not loaded');
        }
        
        const modalElement = document.getElementById('categoryModal');
        if (!modalElement) {
            throw new Error('Modal element not found');
        }
        
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        
        console.log('Modal opened successfully!');
        showToast('Modal test successful!', 'success');
    } catch (error) {
        console.error('Modal test error:', error);
        alert('Modal Error: ' + error.message);
    }
}

// Edit category function with better error handling
function editCategory(category) {
    console.log('Editing category:', category);
    
    try {
        if (!category || !category.id) {
            throw new Error('Invalid category data');
        }
        
        document.getElementById('categoryModalTitle').textContent = 'Edit Category';
        document.getElementById('categoryAction').value = 'update';
        document.getElementById('categoryId').value = category.id;
        document.getElementById('categoryName').value = category.name || '';
        document.getElementById('categoryDescription').value = category.description || '';
        document.getElementById('categoryColor').value = category.color || '#007bff';
        document.getElementById('categorySaveBtn').textContent = 'Update Category';
        
        const modalElement = document.getElementById('categoryModal');
        if (!modalElement) {
            throw new Error('Modal element not found');
        }
        
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        
        console.log('Edit modal opened successfully');
    } catch (error) {
        console.error('Edit category error:', error);
        alert('Error opening edit modal: ' + error.message);
    }
}

// Delete category function with better error handling
function deleteCategory(id, name) {
    console.log('Deleting category:', id, name);
    
    try {
        if (!id || !name) {
            throw new Error('Invalid category data for deletion');
        }
        
        document.getElementById('deleteCategoryName').textContent = name;
        document.getElementById('deleteCategoryId').value = id;
        
        const modalElement = document.getElementById('deleteCategoryModal');
        if (!modalElement) {
            throw new Error('Delete modal element not found');
        }
        
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        
        console.log('Delete modal opened successfully');
    } catch (error) {
        console.error('Delete category error:', error);
        alert('Error opening delete modal: ' + error.message);
    }
}

// Export categories
function exportCategories() {
    console.log('Exporting categories...');
    try {
        const categories = " . json_encode($categories) . ";
        const dataStr = JSON.stringify(categories, null, 2);
        const dataBlob = new Blob([dataStr], {type: 'application/json'});
        const url = URL.createObjectURL(dataBlob);
        
        const link = document.createElement('a');
        link.href = url;
        link.download = 'categories-' + new Date().toISOString().split('T')[0] + '.json';
        link.click();
        
        URL.revokeObjectURL(url);
        showToast('Categories exported successfully', 'success');
        console.log('Export completed successfully');
    } catch (error) {
        console.error('Export error:', error);
        alert('Export failed: ' + error.message);
    }
}

// Toast notification function
function showToast(message, type = 'info') {
    console.log('Toast:', type, message);
    
    // Create toast element if it doesn't exist
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    const toastId = 'toast-' + Date.now();
    const toastHTML = `
        <div id=\"${toastId}\" class=\"toast\" role=\"alert\">
            <div class=\"toast-header\">
                <div class=\"rounded me-2\" style=\"width: 20px; height: 20px; background-color: var(--bs-${type});\"></div>
                <strong class=\"me-auto\">Categories</strong>
                <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"toast\"></button>
            </div>
            <div class=\"toast-body\">${message}</div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    // Remove toast after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

console.log('Categories JavaScript loaded successfully');
</script>
";

// Include the admin layout footer
require_once 'includes/admin_layout_footer.php';
?>
