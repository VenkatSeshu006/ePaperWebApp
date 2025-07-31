<?php
/**
 * Admin Manage Editions Interface
 * View and manage all published editions
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
$pageTitle = 'Manage Editions';
$pageSubtitle = 'View and manage all published editions';

$message = '';
$messageType = '';

// Handle delete action
if (isset($_GET['delete']) && isset($_GET['id'])) {
    try {
        $edition = new Edition();
        $result = $edition->delete($_GET['id']);
        
        if ($result) {
            $message = 'Edition deleted successfully!';
            $messageType = 'success';
        } else {
            throw new Exception('Failed to delete edition');
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// Get editions
$editions = [];
$totalEditions = 0;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 10;

try {
    $edition = new Edition();
    $editions = $edition->getAll($currentPage, $itemsPerPage);
    $totalEditions = $edition->getTotalCount();
} catch (Exception $e) {
    $message = 'Error loading editions: ' . $e->getMessage();
    $messageType = 'danger';
}

$totalPages = ceil($totalEditions / $itemsPerPage);

// Set alert message for layout
if ($message) {
    $alertMessage = $message;
    $alertType = $messageType;
}

// Include the admin layout
require_once 'includes/admin_layout.php';
?>

<!-- Manage Editions Content -->
<div class="row mb-4">
    <div class="col-12">
        <div class="admin-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-newspaper"></i>
                    All Editions (<?php echo $totalEditions; ?>)
                </h5>
                <div>
                    <a href="upload.php" class="btn btn-admin-primary">
                        <i class="fas fa-plus"></i>
                        Add New Edition
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($editions)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-4x text-gray-300 mb-3"></i>
                        <h5 class="text-gray-500">No editions found</h5>
                        <p class="text-muted">Start by uploading your first edition</p>
                        <a href="upload.php" class="btn btn-admin-primary">
                            <i class="fas fa-upload"></i> Upload First Edition
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" class="form-control" id="searchInput" 
                                       placeholder="Search editions...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="categoryFilter">
                                <option value="">All Categories</option>
                                <option value="general">General</option>
                                <option value="news">News</option>
                                <option value="sports">Sports</option>
                                <option value="business">Business</option>
                                <option value="lifestyle">Lifestyle</option>
                                <option value="technology">Technology</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="sortFilter">
                                <option value="newest">Newest First</option>
                                <option value="oldest">Oldest First</option>
                                <option value="most_viewed">Most Viewed</option>
                                <option value="title">Title A-Z</option>
                            </select>
                        </div>
                    </div>

                    <!-- Editions Table -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="editionsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Edition</th>
                                    <th>Category</th>
                                    <th>Publication Date</th>
                                    <th>Views</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($editions as $edition): ?>
                                <tr data-category="<?php echo htmlspecialchars($edition['category'] ?? 'general'); ?>"
                                    data-title="<?php echo htmlspecialchars(strtolower($edition['title'])); ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <?php if (!empty($edition['thumbnail_path']) && file_exists('../' . $edition['thumbnail_path'])): ?>
                                                    <img src="../<?php echo htmlspecialchars($edition['thumbnail_path']); ?>" 
                                                         alt="Thumbnail" class="rounded" 
                                                         style="width: 60px; height: 60px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                                         style="width: 60px; height: 60px;">
                                                        <i class="fas fa-newspaper text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($edition['title']); ?></h6>
                                                <p class="text-muted small mb-0">
                                                    <?php echo htmlspecialchars(substr($edition['description'] ?? '', 0, 80)); ?>
                                                    <?php if (strlen($edition['description'] ?? '') > 80): ?>...<?php endif; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo ucfirst($edition['category'] ?? 'general'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="small">
                                            <?php echo date('M j, Y', strtotime($edition['publication_date'] ?? $edition['created_at'])); ?>
                                        </span>
                                        <br>
                                        <span class="text-muted smaller">
                                            <?php echo date('g:i A', strtotime($edition['created_at'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo number_format($edition['views'] ?? 0); ?> views
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Published</span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="../view.php?id=<?php echo $edition['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" 
                                               target="_blank" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $edition['id']; ?>" 
                                               class="btn btn-sm btn-outline-secondary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger" 
                                                    onclick="confirmDelete(<?php echo $edition['id']; ?>, '<?php echo htmlspecialchars(addslashes($edition['title'])); ?>')"
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

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Editions pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($totalPages, $currentPage + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                            <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Custom JavaScript -->
<?php 
$additionalJS = "
<script>
// Search functionality
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#editionsTable tbody tr');
    
    rows.forEach(row => {
        const title = row.getAttribute('data-title');
        if (title.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Category filter
document.getElementById('categoryFilter').addEventListener('change', function() {
    const selectedCategory = this.value;
    const rows = document.querySelectorAll('#editionsTable tbody tr');
    
    rows.forEach(row => {
        const category = row.getAttribute('data-category');
        if (!selectedCategory || category === selectedCategory) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Delete confirmation
function confirmDelete(id, title) {
    if (confirm('Are you sure you want to delete \"' + title + '\"?\\n\\nThis action cannot be undone.')) {
        window.location.href = '?delete=1&id=' + id;
    }
}

// Sort functionality
document.getElementById('sortFilter').addEventListener('change', function() {
    const sortType = this.value;
    const tbody = document.querySelector('#editionsTable tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        switch(sortType) {
            case 'title':
                return a.getAttribute('data-title').localeCompare(b.getAttribute('data-title'));
            case 'newest':
                // This would need proper date comparison in a real implementation
                return 0;
            case 'oldest':
                return 0;
            case 'most_viewed':
                return 0;
            default:
                return 0;
        }
    });
    
    rows.forEach(row => tbody.appendChild(row));
});
</script>
";

// Include the admin layout footer
require_once 'includes/admin_layout_footer.php';
?>
