<?php
/**
 * Clips Management Page
 * Manage user-created content clips
 */
session_start();
define('ADMIN_PAGE', true);

// Include configuration first
require_once '../config/config.php';

// Authentication check
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

// Get database connection
$conn = getConnection();

// Ensure clips table exists (in case it wasn't created yet)
$tableCheckSql = "CREATE TABLE IF NOT EXISTS clips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    edition_id INT NOT NULL,
    image_id INT DEFAULT 1,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_edition_id (edition_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$conn->query($tableCheckSql);

// Initialize variables
$success = '';
$error = '';
$clips = [];
$editions = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'delete_clip':
            $clip_id = (int)($_POST['clip_id'] ?? 0);
            
            // Get clip info first to delete the file
            $clipSql = "SELECT file_path FROM clips WHERE id = ?";
            $clipStmt = $conn->prepare($clipSql);
            $clipStmt->bind_param("i", $clip_id);
            $clipStmt->execute();
            $clipResult = $clipStmt->get_result();
            $clip = $clipResult->fetch_assoc();
            
            if ($clip) {
                // Delete the file if it exists
                $fullPath = '../' . $clip['file_path'];
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
                
                // Delete from database
                $deleteSql = "DELETE FROM clips WHERE id = ?";
                $deleteStmt = $conn->prepare($deleteSql);
                $deleteStmt->bind_param("i", $clip_id);
                
                if ($deleteStmt->execute()) {
                    $success = 'Clip deleted successfully.';
                } else {
                    $error = 'Error deleting clip: ' . $conn->error;
                }
            } else {
                $error = 'Clip not found.';
            }
            break;
            
        case 'update_clip':
            $clip_id = (int)($_POST['clip_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($title)) {
                $error = 'Title is required.';
            } else {
                $updateSql = "UPDATE clips SET title = ?, description = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("ssi", $title, $description, $clip_id);
                
                if ($updateStmt->execute()) {
                    $success = 'Clip updated successfully.';
                } else {
                    $error = 'Error updating clip: ' . $conn->error;
                }
            }
            break;
            
        case 'bulk_delete':
            $clip_ids = $_POST['clip_ids'] ?? [];
            if (!empty($clip_ids)) {
                $placeholders = str_repeat('?,', count($clip_ids) - 1) . '?';
                
                // Get file paths to delete files
                $filesSql = "SELECT file_path FROM clips WHERE id IN ($placeholders)";
                $filesStmt = $conn->prepare($filesSql);
                $filesStmt->bind_param(str_repeat('i', count($clip_ids)), ...$clip_ids);
                $filesStmt->execute();
                $filesResult = $filesStmt->get_result();
                
                // Delete files
                while ($file = $filesResult->fetch_assoc()) {
                    $fullPath = '../' . $file['file_path'];
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }
                
                // Delete from database
                $deleteSql = "DELETE FROM clips WHERE id IN ($placeholders)";
                $deleteStmt = $conn->prepare($deleteSql);
                $deleteStmt->bind_param(str_repeat('i', count($clip_ids)), ...$clip_ids);
                
                if ($deleteStmt->execute()) {
                    $success = count($clip_ids) . ' clips deleted successfully.';
                } else {
                    $error = 'Error deleting clips: ' . $conn->error;
                }
            }
            break;
    }
}

// Get filter parameters
$edition_filter = $_GET['edition'] ?? '';
$date_filter = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';

// Build WHERE clause for filters
$whereConditions = [];
$params = [];
$paramTypes = '';

if (!empty($edition_filter)) {
    $whereConditions[] = "c.edition_id = ?";
    $params[] = $edition_filter;
    $paramTypes .= 'i';
}

if (!empty($date_filter)) {
    $whereConditions[] = "DATE(c.created_at) = ?";
    $params[] = $date_filter;
    $paramTypes .= 's';
}

if (!empty($search)) {
    $whereConditions[] = "(c.title LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $paramTypes .= 'ss';
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get clips with edition information
$clipsSql = "SELECT c.*, e.title as edition_title, e.date as edition_date 
             FROM clips c 
             LEFT JOIN editions e ON c.edition_id = e.id 
             $whereClause 
             ORDER BY c.created_at DESC";

if (!empty($params)) {
    $clipsStmt = $conn->prepare($clipsSql);
    $clipsStmt->bind_param($paramTypes, ...$params);
    $clipsStmt->execute();
    $clipsResult = $clipsStmt->get_result();
} else {
    $clipsResult = $conn->query($clipsSql);
}

if ($clipsResult) {
    while ($row = $clipsResult->fetch_assoc()) {
        $clips[] = $row;
    }
}

// Get all editions for filter dropdown
$editionsSql = "SELECT id, title, date FROM editions ORDER BY date DESC";
$editionsResult = $conn->query($editionsSql);
if ($editionsResult) {
    while ($row = $editionsResult->fetch_assoc()) {
        $editions[] = $row;
    }
}

// Get statistics
$statsSql = "SELECT 
    COUNT(*) as total_clips,
    COUNT(DISTINCT edition_id) as editions_with_clips,
    DATE(MIN(created_at)) as first_clip_date,
    DATE(MAX(created_at)) as last_clip_date
    FROM clips";
$statsResult = $conn->query($statsSql);
$stats = $statsResult ? $statsResult->fetch_assoc() : [];

// Page configuration for shared layout
$pageTitle = 'Clips Management';
$pageSubtitle = 'Manage user-created content clips';
$currentPage = 'clips';
$pageIcon = 'fas fa-cut';

// Include shared layout header
include 'includes/admin_layout.php';
?>

<div class="row">
    <div class="col-12">
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Statistics Row -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?php echo number_format($stats['total_clips'] ?? 0); ?></h4>
                        <p class="card-text">Total Clips</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-cut fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?php echo number_format($stats['editions_with_clips'] ?? 0); ?></h4>
                        <p class="card-text">Editions with Clips</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-newspaper fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?php echo $stats['first_clip_date'] ? date('M j', strtotime($stats['first_clip_date'])) : 'N/A'; ?></h4>
                        <p class="card-text">First Clip</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-calendar-plus fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?php echo $stats['last_clip_date'] ? date('M j', strtotime($stats['last_clip_date'])) : 'N/A'; ?></h4>
                        <p class="card-text">Latest Clip</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-calendar-check fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="edition" class="form-label">Filter by Edition</label>
                        <select class="form-select" id="edition" name="edition">
                            <option value="">All Editions</option>
                            <?php foreach ($editions as $edition): ?>
                                <option value="<?php echo $edition['id']; ?>" <?php echo $edition_filter == $edition['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($edition['title']); ?> (<?php echo date('M j, Y', strtotime($edition['date'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date" class="form-label">Filter by Date</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="Search title or description..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Filter
                            </button>
                        </div>
                    </div>
                </form>
                <?php if ($edition_filter || $date_filter || $search): ?>
                    <div class="mt-3">
                        <a href="clips.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times me-2"></i>Clear Filters
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Clips Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-cut me-2 text-primary"></i>User Clips
                    <?php if (!empty($clips)): ?>
                        <span class="badge bg-primary ms-2"><?php echo count($clips); ?></span>
                    <?php endif; ?>
                </h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="bulkDelete()" id="bulkDeleteBtn" style="display: none;">
                        <i class="fas fa-trash me-2"></i>Delete Selected
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($clips)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-cut fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No clips found. Users haven't created any clips yet.</p>
                        <?php if ($edition_filter || $date_filter || $search): ?>
                            <a href="clips.php" class="btn btn-outline-primary">View All Clips</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <form id="bulkForm" method="POST">
                        <input type="hidden" name="action" value="bulk_delete">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                        </th>
                                        <th>Preview</th>
                                        <th>Title</th>
                                        <th>Edition</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clips as $clip): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="clip_ids[]" value="<?php echo $clip['id']; ?>" onchange="updateBulkActions()">
                                            </td>
                                            <td>
                                                <div class="clip-preview">
                                                    <?php if (file_exists('../' . $clip['file_path'])): ?>
                                                        <img src="../<?php echo htmlspecialchars($clip['file_path']); ?>" 
                                                             alt="Clip Preview" 
                                                             class="img-thumbnail"
                                                             style="max-width: 80px; max-height: 60px; cursor: pointer;"
                                                             onclick="showPreview('<?php echo htmlspecialchars($clip['file_path']); ?>', '<?php echo htmlspecialchars($clip['title']); ?>')">
                                                    <?php else: ?>
                                                        <div class="bg-light d-flex align-items-center justify-content-center" style="width: 80px; height: 60px;">
                                                            <i class="fas fa-image text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($clip['title']); ?></strong>
                                                <?php if ($clip['description']): ?>
                                                    <br><small class="text-muted"><?php echo nl2br(htmlspecialchars(substr($clip['description'], 0, 100))); ?><?php echo strlen($clip['description']) > 100 ? '...' : ''; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($clip['edition_title']): ?>
                                                    <strong><?php echo htmlspecialchars($clip['edition_title']); ?></strong>
                                                    <br><small class="text-muted"><?php echo date('M j, Y', strtotime($clip['edition_date'])); ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">Unknown Edition</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php echo date('M j, Y g:i A', strtotime($clip['created_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            onclick="editClip(<?php echo htmlspecialchars(json_encode($clip)); ?>)"
                                                            title="Edit Clip">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if (file_exists('../' . $clip['file_path'])): ?>
                                                        <a href="../<?php echo htmlspecialchars($clip['file_path']); ?>" 
                                                           class="btn btn-outline-success" 
                                                           download
                                                           title="Download Clip">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="deleteClip(<?php echo $clip['id']; ?>, '<?php echo htmlspecialchars($clip['title']); ?>')"
                                                            title="Delete Clip">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Clip Modal -->
<div class="modal fade" id="editClipModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="update_clip">
                <input type="hidden" name="clip_id" id="edit_clip_id">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Clip
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Title *</label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Clip
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Clip Modal -->
<div class="modal fade" id="deleteClipModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="delete_clip">
                <input type="hidden" name="clip_id" id="delete_clip_id">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Delete Clip
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the clip <strong id="delete_clip_title"></strong>?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This action cannot be undone. The clip file will be permanently deleted from the server.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Clip
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewTitle">Clip Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="previewImage" src="" alt="Clip Preview" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<script>
function editClip(clip) {
    document.getElementById('edit_clip_id').value = clip.id;
    document.getElementById('edit_title').value = clip.title;
    document.getElementById('edit_description').value = clip.description || '';
    
    const editModal = new bootstrap.Modal(document.getElementById('editClipModal'));
    editModal.show();
}

function deleteClip(clipId, title) {
    document.getElementById('delete_clip_id').value = clipId;
    document.getElementById('delete_clip_title').textContent = title;
    
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteClipModal'));
    deleteModal.show();
}

function showPreview(imagePath, title) {
    document.getElementById('previewImage').src = '../' + imagePath;
    document.getElementById('previewTitle').textContent = title;
    
    const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
    previewModal.show();
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('input[name="clip_ids[]"]');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateBulkActions();
}

function updateBulkActions() {
    const checkedBoxes = document.querySelectorAll('input[name="clip_ids[]"]:checked');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    
    if (checkedBoxes.length > 0) {
        bulkDeleteBtn.style.display = 'inline-block';
    } else {
        bulkDeleteBtn.style.display = 'none';
    }
}

function bulkDelete() {
    const checkedBoxes = document.querySelectorAll('input[name="clip_ids[]"]:checked');
    
    if (checkedBoxes.length === 0) {
        alert('Please select clips to delete.');
        return;
    }
    
    if (confirm(`Are you sure you want to delete ${checkedBoxes.length} selected clips? This action cannot be undone.`)) {
        document.getElementById('bulkForm').submit();
    }
}
</script>

<?php include 'includes/admin_layout_footer.php'; ?>
