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
$pageSubtitle = 'View and manage all editions';

$message = '';
$messageType = '';

// Handle publish action
if (isset($_GET['publish']) && isset($_GET['id'])) {
    try {
        $edition = new Edition();
        $result = $edition->update($_GET['id'], ['status' => 'published']);
        
        if ($result) {
            $message = 'Edition published successfully!';
            $messageType = 'success';
        } else {
            throw new Exception('Failed to publish edition');
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

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
$currentPage = isset($_GET['page']) ? max(1, (int)($_GET['page'] ?? 1)) : 1; // Ensure minimum page is 1
$itemsPerPage = 10;

try {
    $edition = new Edition();
    $offset = max(0, ($currentPage - 1) * $itemsPerPage); // Ensure offset is never negative
    $editions = $edition->getAll($itemsPerPage, $offset);
    $totalEditions = $edition->getTotalCountAll(); // Use getTotalCountAll for admin interface
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
                <div>
                    <h5 class="mb-1">
                        <i class="fas fa-newspaper"></i>
                        All Editions (<?php echo $totalEditions; ?>)
                    </h5>
                    <?php
                    // Count published vs draft
                    $publishedCount = 0;
                    $draftCount = 0;
                    foreach ($editions as $edition) {
                        if (($edition['status'] ?? 'draft') === 'published') {
                            $publishedCount++;
                        } else {
                            $draftCount++;
                        }
                    }
                    ?>
                    <small class="text-muted">
                        <span class="badge bg-success me-1"><?php echo $publishedCount; ?> Published</span>
                        <span class="badge bg-warning"><?php echo $draftCount; ?> Draft</span>
                    </small>
                </div>
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
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" class="form-control" id="searchInput" 
                                       placeholder="Search editions...">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="published">Published</option>
                                <option value="draft">Draft</option>
                            </select>
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
                                <tr data-category="<?php echo htmlspecialchars($edition['category'] ?? 'general', ENT_QUOTES, 'UTF-8'); ?>"
                                    data-status="<?php echo htmlspecialchars($edition['status'] ?? 'draft', ENT_QUOTES, 'UTF-8'); ?>"
                                    data-title="<?php echo htmlspecialchars(strtolower($edition['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <?php if (!empty(($edition['thumbnail_path'] ?? '')) && file_exists('../' . ($edition['thumbnail_path'] ?? ''))): ?>
                                                    <img src="../<?php echo htmlspecialchars(($edition['thumbnail_path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" 
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
                                                <h6 class="mb-1"><?php echo htmlspecialchars(($edition['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h6>
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
                                            <?php echo date('M j, Y', strtotime($edition['publication_date'] ?? ($edition['created_at'] ?? ''))); ?>
                                        </span>
                                        <br>
                                        <span class="text-muted smaller">
                                            <?php echo date('g:i A', strtotime(($edition['created_at'] ?? ''))); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo number_format($edition['views'] ?? 0); ?> views
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $status = $edition['status'] ?? 'draft';
                                        $statusClass = $status === 'published' ? 'bg-success' : 'bg-warning';
                                        $statusText = ucfirst($status);
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="../view-edition.php?id=<?php echo ($edition['id'] ?? ''); ?>" 
                                               class="btn btn-sm btn-outline-primary" 
                                               target="_blank" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_edition.php?id=<?php echo ($edition['id'] ?? ''); ?>" 
                                               class="btn btn-sm btn-outline-secondary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if (($edition['status'] ?? '') === 'draft'): ?>
                                            <a href="?publish=<?php echo ($edition['id'] ?? ''); ?>&page=<?php echo $currentPage; ?>" 
                                               class="btn btn-sm btn-outline-success" 
                                               onclick="return confirm('Publish this edition?')" 
                                               title="Publish">
                                                <i class="fas fa-paper-plane"></i>
                                            </a>
                                            <?php endif; ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger" 
                                                    onclick="confirmDelete(<?php echo ($edition['id'] ?? ''); ?>, '<?php echo htmlspecialchars(addslashes($edition['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>')"
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
    filterTable();
});

// Category filter
document.getElementById('categoryFilter').addEventListener('change', function() {
    filterTable();
});

// Status filter
document.getElementById('statusFilter').addEventListener('change', function() {
    filterTable();
});

// Combined filter function
function filterTable() {
    const selectedCategory = document.getElementById('categoryFilter').value;
    const selectedStatus = document.getElementById('statusFilter').value;
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#editionsTable tbody tr');
    
    rows.forEach(row => {
        const category = row.getAttribute('data-category');
        const status = row.getAttribute('data-status');
        const title = row.getAttribute('data-title');
        
        const categoryMatch = !selectedCategory || category === selectedCategory;
        const statusMatch = !selectedStatus || status === selectedStatus;
        const searchMatch = !searchTerm || title.includes(searchTerm);
        
        if (categoryMatch && statusMatch && searchMatch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

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
