<?php
/**
 * Admin Categories Interface
 * Manage content categories for E-Paper CMS
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
$pageTitle = 'Categories';
$pageSubtitle = 'Manage content categories and tags';

$message = '';
$messageType = '';

// Debug: Log form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log("Categories POST request received");
    error_log("POST data: " . print_r($_POST, true));
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
        error_log("Action: " . $action);
        
        switch ($action) {
            case 'create':
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $color = $_POST['color'] ?? '#007bff';
                
                error_log("Creating category: $name");
                
                if (empty($name)) {
                    throw new Exception('Category name is required');
                }
                
                $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $name));
                
                $stmt = $conn->prepare("INSERT INTO categories (name, slug, description, color) VALUES (?, ?, ?, ?)");
                $result = $stmt->execute([$name, $slug, $description, $color]);
                
                if ($result) {
                    $message = 'Category created successfully!';
                    $messageType = 'success';
                    error_log("Category created successfully");
                } else {
                    throw new Exception('Failed to create category');
                }
                break;
                
            case 'update':
                $id = (int)($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $color = $_POST['color'] ?? '#007bff';
                
                if ($id <= 0 || empty($name)) {
                    throw new Exception('Invalid category data');
                }
                
                $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $name));
                
                $stmt = $conn->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, color = ? WHERE id = ?");
                $result = $stmt->execute([$name, $slug, $description, $color, $id]);
                
                if ($result) {
                    $message = 'Category updated successfully!';
                    $messageType = 'success';
                } else {
                    throw new Exception('Failed to update category');
                }
                break;
                
            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                
                if ($id <= 0) {
                    throw new Exception('Invalid category ID');
                }
                
                $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
                $result = $stmt->execute([$id]);
                
                if ($result) {
                    $message = 'Category deleted successfully!';
                    $messageType = 'success';
                } else {
                    throw new Exception('Failed to delete category');
                }
                break;
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
                                                    onclick="deleteCategory(<?php echo ($category['id'] ?? ''); ?>, '<?php echo htmlspecialchars(addslashes(($category['name'] ?? ''), ENT_QUOTES, 'UTF-8')); ?>')"
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
document.addEventListener('DOMContentLoaded', function() {
    // Reset modal when hidden
    const categoryModal = document.getElementById('categoryModal');
    if (categoryModal) {
        categoryModal.addEventListener('hidden.bs.modal', function() {
            document.getElementById('categoryModalTitle').textContent = 'Add Category';
            document.getElementById('categoryAction').value = 'create';
            document.getElementById('categoryId').value = '';
            document.getElementById('categoryName').value = '';
            document.getElementById('categoryDescription').value = '';
            document.getElementById('categoryColor').value = '#007bff';
            document.getElementById('categorySaveBtn').textContent = 'Save Category';
        });
    }
});

// Test function to manually open modal
function testAddCategory() {
    try {
        const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
        modal.show();
        alert('Modal opened successfully!');
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

// Edit category function
function editCategory(category) {
    document.getElementById('categoryModalTitle').textContent = 'Edit Category';
    document.getElementById('categoryAction').value = 'update';
    document.getElementById('categoryId').value = category.id;
    document.getElementById('categoryName').value = category.name;
    document.getElementById('categoryDescription').value = category.description || '';
    document.getElementById('categoryColor').value = category.color || '#007bff';
    document.getElementById('categorySaveBtn').textContent = 'Update Category';
    
    var modal = new bootstrap.Modal(document.getElementById('categoryModal'));
    modal.show();
}

// Delete category function
function deleteCategory(id, name) {
    document.getElementById('deleteCategoryName').textContent = name;
    document.getElementById('deleteCategoryId').value = id;
    
    var modal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
    modal.show();
}

// Import categories
function importCategories() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.json';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const categories = JSON.parse(e.target.result);
                    // Process import (would need backend implementation)
                    showToast('Categories imported successfully', 'success');
                } catch (error) {
                    showToast('Invalid file format', 'danger');
                }
            };
            reader.readAsText(file);
        }
    };
    input.click();
}

// Export categories
function exportCategories() {
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
}
</script>
";

// Include the admin layout footer
require_once 'includes/admin_layout_footer.php';
?>
