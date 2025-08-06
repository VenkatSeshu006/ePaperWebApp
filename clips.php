<?php
/**
 * Clips Management System
 * Enhanced clip viewer and management
 */

require_once 'includes/database.php';

// Helper function to format file sizes
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

$clipId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? 'view';
$conn = getConnection();

if (!$conn) {
    die("Database connection failed");
}

// Handle delete action
if ($action === 'delete' && $clipId) {
    $stmt = $conn->prepare("SELECT image_path FROM clips WHERE id = ?");
    $stmt->execute([$clipId]);
    $result = $stmt->get_result();
    $clip = $result->fetch();
    
    if ($clip) {
        // Delete file if it exists
        if (file_exists($clip['image_path'])) {
            unlink($clip['image_path']);
        }
        
        // Delete from database
        $stmt = $conn->prepare("DELETE FROM clips WHERE id = ?");
        $stmt->execute([$clipId]);
        
        header("Location: clips.php?deleted=1");
        exit;
    }
}

if ($clipId && $action === 'view') {
    // Get specific clip with edition info
    $stmt = $conn->prepare("
        SELECT c.*, e.title as edition_title, e.date as edition_date 
        FROM clips c 
        LEFT JOIN editions e ON c.edition_id = e.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$clipId]);
    $result = $stmt->get_result();
    $clip = $result->fetch();
    
    if (!$clip) {
        header("HTTP/1.0 404 Not Found");
        echo "Clip not found";
        exit;
    }
} else {
    // Get all clips with pagination and search
    $search = $_GET['search'] ?? '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 12;
    $offset = ($page - 1) * $limit;
    
    $whereClause = '';
    $params = [];
    $types = '';
    
    if ($search) {
        $whereClause = "WHERE (c.title LIKE ? OR c.description LIKE ? OR e.title LIKE ?)";
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam, $searchParam];
        $types = 'sss';
    }
    
    // Get total count
    $countSql = "
        SELECT COUNT(*) as total 
        FROM clips c 
        LEFT JOIN editions e ON c.edition_id = e.id 
        $whereClause
    ";
    
    if ($params) {
        $countStmt = $conn->prepare($countSql);
        $countStmt->bind_param($types, ...$params);
        $countStmt->execute();
        $totalClips = $countStmt->get_result()->fetch()['total'];
    } else {
        $totalClips = $conn->query($countSql)->fetch()['total'];
    }
    
    $totalPages = ceil($totalClips / $limit);
    
    // Get clips
    $sql = "
        SELECT c.*, e.title as edition_title, e.date as edition_date 
        FROM clips c 
        LEFT JOIN editions e ON c.edition_id = e.id 
        $whereClause 
        ORDER BY c.created_at DESC 
        LIMIT ? OFFSET ?
    ";
    
    $allParams = array_merge($params, [$limit, $offset]);
    $allTypes = $types . 'ii';
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($allTypes, ...$allParams);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $clips = [];
    while ($row = $result->fetch()) {
        $clips[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $clipId && $clip ? htmlspecialchars($clip['title']) : 'Clips Management'; ?> - E-Paper CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .clip-thumbnail {
            height: 200px;
            overflow: hidden;
            border-radius: 8px;
        }
        .clip-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .clip-thumbnail:hover img {
            transform: scale(1.05);
        }
        .clip-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .clip-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .clip-actions {
            gap: 5px;
        }
        .btn-icon {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }
        .search-box {
            max-width: 400px;
        }
        .clip-stats {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }
        .modal-backdrop.show {
            opacity: 0.7;
        }
        .share-platform {
            transition: all 0.2s ease;
        }
        .share-platform:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-newspaper"></i> E-Paper CMS
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home"></i> Homepage
                </a>
                <a class="nav-link active" href="clips.php">
                    <i class="fas fa-cut"></i> Clips
                </a>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> Clip deleted successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($clipId && $clip && $action === 'view'): ?>
        <!-- Single Clip View -->
        <div class="row">
            <div class="col-12 mb-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="clips.php">Clips</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($clip['title']); ?></li>
                    </ol>
                </nav>
            </div>
            
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-0"><i class="fas fa-cut"></i> <?php echo htmlspecialchars($clip['title']); ?></h4>
                                <small class="text-white-50">
                                    From: <?php echo htmlspecialchars($clip['edition_title'] ?? 'Unknown Edition'); ?> 
                                    | <?php echo date('M j, Y g:i A', strtotime($clip['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <?php 
                            $imagePath = $clip['image_path'];
                            // Handle both old 'file_path' and new 'image_path' columns
                            if (empty($imagePath) && !empty($clip['file_path'])) {
                                $imagePath = $clip['file_path'];
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                 class="img-fluid rounded shadow" 
                                 alt="<?php echo htmlspecialchars($clip['title']); ?>"
                                 style="max-height: 600px;">
                        </div>
                        
                        <?php if ($clip['description']): ?>
                        <div class="text-start">
                            <h6>Description:</h6>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($clip['description'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Actions Panel -->
                <div class="card shadow-sm mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-tools"></i> Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" onclick="shareClip(<?php echo $clip['id']; ?>)">
                                <i class="fas fa-share-alt"></i> Share Clip
                            </button>
                            <a href="<?php echo htmlspecialchars($imagePath); ?>" 
                               download="<?php echo htmlspecialchars($clip['title']); ?>.jpg" 
                               class="btn btn-success">
                                <i class="fas fa-download"></i> Download Original
                            </a>
                            <button class="btn btn-info" onclick="copyToClipboard('<?php echo htmlspecialchars(addslashes($imagePath)); ?>')">
                                <i class="fas fa-copy"></i> Copy Image URL
                            </button>
                            <button class="btn btn-warning" onclick="editClip(<?php echo $clip['id']; ?>)">
                                <i class="fas fa-edit"></i> Edit Details
                            </button>
                            <button class="btn btn-danger" onclick="deleteClip(<?php echo $clip['id']; ?>)">
                                <i class="fas fa-trash"></i> Delete Clip
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Clip Details -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-info-circle"></i> Details</h6>
                    </div>
                    <div class="card-body">
                        <dl class="row small">
                            <dt class="col-5">Edition:</dt>
                            <dd class="col-7"><?php echo htmlspecialchars($clip['edition_title'] ?? 'Unknown'); ?></dd>
                            
                            <dt class="col-5">Date:</dt>
                            <dd class="col-7"><?php echo $clip['edition_date'] ? date('M j, Y', strtotime($clip['edition_date'])) : 'Unknown'; ?></dd>
                            
                            <dt class="col-5">Created:</dt>
                            <dd class="col-7"><?php echo date('M j, Y g:i A', strtotime($clip['created_at'])); ?></dd>
                            
                            <dt class="col-5">File Size:</dt>
                            <dd class="col-7">
                                <?php 
                                if (file_exists($imagePath)) {
                                    echo formatBytes(filesize($imagePath));
                                } else {
                                    echo 'File not found';
                                }
                                ?>
                            </dd>
                            
                            <dt class="col-5">Dimensions:</dt>
                            <dd class="col-7">
                                <?php 
                                if (file_exists($imagePath)) {
                                    $imageInfo = getimagesize($imagePath);
                                    if ($imageInfo) {
                                        echo $imageInfo[0] . ' × ' . $imageInfo[1] . ' px';
                                    } else {
                                        echo 'Unknown';
                                    }
                                } else {
                                    echo 'Unknown';
                                }
                                ?>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <?php else: ?>
        <!-- All Clips View -->
        <div class="row">
            <div class="col-12">
                <!-- Header Section -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
                    <div>
                        <h2><i class="fas fa-cut"></i> Clips Management</h2>
                        <p class="text-muted">Manage your saved newspaper clips</p>
                    </div>
                    <div class="d-flex gap-2 mt-2 mt-md-0">
                        <a href="index.php" class="btn btn-outline-primary">
                            <i class="fas fa-newspaper"></i> Back to E-Paper
                        </a>
                        <button class="btn btn-primary" onclick="window.location.reload()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                
                <!-- Stats and Search -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="clip-stats p-3">
                            <div class="row text-center">
                                <div class="col-4">
                                    <h4 class="mb-0"><?php echo $totalClips; ?></h4>
                                    <small>Total Clips</small>
                                </div>
                                <div class="col-4">
                                    <h4 class="mb-0"><?php echo $totalPages; ?></h4>
                                    <small>Pages</small>
                                </div>
                                <div class="col-4">
                                    <h4 class="mb-0">
                                        <?php 
                                        $totalSize = 0;
                                        foreach ($clips as $c) {
                                            $path = $c['image_path'] ?: $c['file_path'] ?? '';
                                            if ($path && file_exists($path)) {
                                                $totalSize += filesize($path);
                                            }
                                        }
                                        echo formatBytes($totalSize);
                                        ?>
                                    </h4>
                                    <small>Storage Used</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <form method="GET" class="search-box">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search clips..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if (empty($clips)): ?>
                <!-- Empty State -->
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-cut text-muted" style="font-size: 4rem;"></i>
                    </div>
                    <h3 class="text-muted">No clips found</h3>
                    <p class="text-muted">
                        <?php echo $search ? 'No clips match your search criteria.' : 'Start creating clips from the main newspaper viewer.'; ?>
                    </p>
                    <div class="mt-3">
                        <?php if ($search): ?>
                        <a href="clips.php" class="btn btn-primary me-2">
                            <i class="fas fa-times"></i> Clear Search
                        </a>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn-success">
                            <i class="fas fa-newspaper"></i> Browse E-Paper
                        </a>
                    </div>
                </div>
                
                <?php else: ?>
                <!-- Clips Grid -->
                <div class="row g-4">
                    <?php foreach ($clips as $clip): ?>
                    <?php 
                    $imagePath = $clip['image_path'] ?: $clip['file_path'] ?? '';
                    $imageExists = $imagePath && file_exists($imagePath);
                    ?>
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <div class="card clip-card h-100 shadow-sm">
                            <div class="clip-thumbnail">
                                <?php if ($imageExists): ?>
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                     alt="<?php echo htmlspecialchars($clip['title']); ?>">
                                <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center h-100 bg-light">
                                    <i class="fas fa-image text-muted" style="font-size: 2rem;"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-body">
                                <h6 class="card-title mb-2" title="<?php echo htmlspecialchars($clip['title']); ?>">
                                    <?php echo htmlspecialchars(mb_strimwidth($clip['title'], 0, 30, '...')); ?>
                                </h6>
                                <div class="small text-muted mb-2">
                                    <div>
                                        <i class="fas fa-newspaper"></i> 
                                        <?php echo htmlspecialchars($clip['edition_title'] ?? 'Unknown Edition'); ?>
                                    </div>
                                    <div>
                                        <i class="fas fa-calendar"></i> 
                                        <?php echo date('M j, Y', strtotime($clip['created_at'])); ?>
                                    </div>
                                </div>
                                <?php if ($clip['description']): ?>
                                <p class="card-text small text-muted">
                                    <?php echo htmlspecialchars(mb_strimwidth($clip['description'], 0, 50, '...')); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between clip-actions">
                                    <a href="clips.php?id=<?php echo $clip['id']; ?>" 
                                       class="btn btn-sm btn-primary btn-icon" 
                                       title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button onclick="shareClip(<?php echo $clip['id']; ?>)" 
                                            class="btn btn-sm btn-info btn-icon" 
                                            title="Share">
                                        <i class="fas fa-share-alt"></i>
                                    </button>
                                    <?php if ($imageExists): ?>
                                    <a href="<?php echo htmlspecialchars($imagePath); ?>" 
                                       download="<?php echo htmlspecialchars($clip['title']); ?>.jpg" 
                                       class="btn btn-sm btn-success btn-icon" 
                                       title="Download">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <?php else: ?>
                                    <button class="btn btn-sm btn-secondary btn-icon" 
                                            title="File not found" disabled>
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button onclick="deleteClip(<?php echo $clip['id']; ?>)" 
                                            class="btn btn-sm btn-danger btn-icon" 
                                            title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Clips pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                        <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $p; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                <?php echo $p; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    </div>

    <!-- Share Modal -->
    <div class="modal fade" id="shareModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-share-alt"></i> Share Clip</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Quick Actions -->
                        <div class="col-12">
                            <h6><i class="fas fa-bolt"></i> Quick Actions</h6>
                            <div class="d-flex gap-2 flex-wrap">
                                <button class="btn btn-outline-primary btn-sm" onclick="copyClipUrl()">
                                    <i class="fas fa-copy"></i> Copy Link
                                </button>
                                <button class="btn btn-outline-success btn-sm" onclick="downloadClipImage()">
                                    <i class="fas fa-download"></i> Download
                                </button>
                                <button class="btn btn-outline-info btn-sm" onclick="generateQRCode()">
                                    <i class="fas fa-qrcode"></i> QR Code
                                </button>
                            </div>
                        </div>
                        
                        <!-- Social Media Sharing -->
                        <div class="col-12">
                            <h6><i class="fas fa-share-alt"></i> Share on Social Media</h6>
                            <div class="row g-2">
                                <div class="col-6 col-md-3">
                                    <button class="btn btn-outline-primary w-100 share-platform" onclick="shareOn('facebook')">
                                        <i class="fab fa-facebook-f"></i> Facebook
                                    </button>
                                </div>
                                <div class="col-6 col-md-3">
                                    <button class="btn btn-outline-info w-100 share-platform" onclick="shareOn('twitter')">
                                        <i class="fab fa-twitter"></i> Twitter
                                    </button>
                                </div>
                                <div class="col-6 col-md-3">
                                    <button class="btn btn-outline-success w-100 share-platform" onclick="shareOn('whatsapp')">
                                        <i class="fab fa-whatsapp"></i> WhatsApp
                                    </button>
                                </div>
                                <div class="col-6 col-md-3">
                                    <button class="btn btn-outline-primary w-100 share-platform" onclick="shareOn('linkedin')">
                                        <i class="fab fa-linkedin-in"></i> LinkedIn
                                    </button>
                                </div>
                                <div class="col-6 col-md-3">
                                    <button class="btn btn-outline-danger w-100 share-platform" onclick="shareOn('pinterest')">
                                        <i class="fab fa-pinterest-p"></i> Pinterest
                                    </button>
                                </div>
                                <div class="col-6 col-md-3">
                                    <button class="btn btn-outline-warning w-100 share-platform" onclick="shareOn('reddit')">
                                        <i class="fab fa-reddit-alien"></i> Reddit
                                    </button>
                                </div>
                                <div class="col-6 col-md-3">
                                    <button class="btn btn-outline-secondary w-100 share-platform" onclick="shareOn('email')">
                                        <i class="fas fa-envelope"></i> Email
                                    </button>
                                </div>
                                <div class="col-6 col-md-3">
                                    <button class="btn btn-outline-dark w-100 share-platform" onclick="shareOn('telegram')">
                                        <i class="fab fa-telegram-plane"></i> Telegram
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Share URL -->
                        <div class="col-12">
                            <h6><i class="fas fa-link"></i> Direct Link</h6>
                            <div class="input-group">
                                <input type="text" class="form-control" id="shareUrl" readonly>
                                <button class="btn btn-outline-secondary" onclick="copyClipUrl()">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- QR Code -->
                        <div class="col-12" id="qrCodeSection" style="display: none;">
                            <h6><i class="fas fa-qrcode"></i> QR Code</h6>
                            <div class="text-center">
                                <div id="qrcode"></div>
                                <button class="btn btn-sm btn-success mt-2" onclick="downloadQRCode()">
                                    <i class="fas fa-download"></i> Download QR Code
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Clip Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Clip</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editClipForm">
                    <div class="modal-body">
                        <input type="hidden" id="editClipId">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" id="editTitle" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="editDescription" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script>
        let currentClipId = null;
        let currentClipData = null;

        function shareClip(clipId) {
            currentClipId = clipId;
            const shareUrl = `${window.location.origin}${window.location.pathname}?id=${clipId}`;
            document.getElementById('shareUrl').value = shareUrl;
            
            // Show share modal
            const shareModal = new bootstrap.Modal(document.getElementById('shareModal'));
            shareModal.show();
        }

        function copyClipUrl() {
            const shareUrl = document.getElementById('shareUrl');
            shareUrl.select();
            navigator.clipboard.writeText(shareUrl.value).then(() => {
                showNotification('✅ Link copied to clipboard!', 'success');
            }).catch(() => {
                showNotification('❌ Failed to copy link', 'error');
            });
        }

        function downloadClipImage() {
            if (!currentClipId) return;
            
            // Find the clip's image URL and trigger download
            const clipUrl = `clips.php?id=${currentClipId}`;
            window.location.href = clipUrl;
        }

        function generateQRCode() {
            const shareUrl = document.getElementById('shareUrl').value;
            const qrSection = document.getElementById('qrCodeSection');
            const qrDiv = document.getElementById('qrcode');
            
            // Clear previous QR code
            qrDiv.innerHTML = '';
            
            // Generate new QR code
            QRCode.toCanvas(qrDiv, shareUrl, {
                width: 200,
                margin: 2,
                color: {
                    dark: '#000000',
                    light: '#FFFFFF'
                }
            }, function (error) {
                if (!error) {
                    qrSection.style.display = 'block';
                    showNotification('✅ QR Code generated!', 'success');
                } else {
                    showNotification('❌ Failed to generate QR Code', 'error');
                }
            });
        }

        function downloadQRCode() {
            const canvas = document.querySelector('#qrcode canvas');
            if (canvas) {
                const link = document.createElement('a');
                link.download = `clip-${currentClipId}-qr.png`;
                link.href = canvas.toDataURL();
                link.click();
                showNotification('✅ QR Code downloaded!', 'success');
            }
        }

        function shareOn(platform) {
            const shareUrl = document.getElementById('shareUrl').value;
            const title = `Check out this clip from E-Paper CMS`;
            const text = `Interesting clip from our digital newspaper`;
            
            let url;
            switch (platform) {
                case 'facebook':
                    url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}`;
                    break;
                case 'twitter':
                    url = `https://twitter.com/intent/tweet?url=${encodeURIComponent(shareUrl)}&text=${encodeURIComponent(text)}`;
                    break;
                case 'linkedin':
                    url = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(shareUrl)}`;
                    break;
                case 'whatsapp':
                    url = `https://wa.me/?text=${encodeURIComponent(text + ' ' + shareUrl)}`;
                    break;
                case 'telegram':
                    url = `https://t.me/share/url?url=${encodeURIComponent(shareUrl)}&text=${encodeURIComponent(text)}`;
                    break;
                case 'reddit':
                    url = `https://reddit.com/submit?url=${encodeURIComponent(shareUrl)}&title=${encodeURIComponent(title)}`;
                    break;
                case 'pinterest':
                    url = `https://pinterest.com/pin/create/button/?url=${encodeURIComponent(shareUrl)}&description=${encodeURIComponent(text)}`;
                    break;
                case 'email':
                    url = `mailto:?subject=${encodeURIComponent(title)}&body=${encodeURIComponent(text + '\n\n' + shareUrl)}`;
                    break;
            }
            
            if (url) {
                window.open(url, '_blank', 'width=600,height=400');
            }
        }

        function editClip(clipId) {
            // Fetch clip data
            fetch(`api/get-clip.php?id=${clipId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editClipId').value = clipId;
                        document.getElementById('editTitle').value = data.clip.title;
                        document.getElementById('editDescription').value = data.clip.description || '';
                        
                        const editModal = new bootstrap.Modal(document.getElementById('editModal'));
                        editModal.show();
                    } else {
                        showNotification('❌ Failed to load clip data', 'error');
                    }
                })
                .catch(error => {
                    showNotification('❌ Error loading clip data', 'error');
                });
        }

        function deleteClip(clipId) {
            if (confirm('Are you sure you want to delete this clip? This action cannot be undone.')) {
                window.location.href = `clips.php?action=delete&id=${clipId}`;
            }
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                showNotification('✅ Image URL copied to clipboard!', 'success');
            }).catch(() => {
                showNotification('❌ Failed to copy text', 'error');
            });
        }

        // Edit form submission
        document.getElementById('editClipForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const clipId = document.getElementById('editClipId').value;
            const title = document.getElementById('editTitle').value;
            const description = document.getElementById('editDescription').value;
            
            fetch('api/update-clip.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: clipId,
                    title: title,
                    description: description
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('✅ Clip updated successfully!', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showNotification('❌ Failed to update clip', 'error');
                }
            })
            .catch(error => {
                showNotification('❌ Error updating clip', 'error');
            });
            
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
        });

        // Notification system
        function showNotification(message, type = 'info') {
            const alertClass = type === 'success' ? 'alert-success' : 
                              type === 'error' ? 'alert-danger' : 'alert-info';
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                ${message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 5000);
        }
    </script>
</body>
</html>
