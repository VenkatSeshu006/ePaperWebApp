<?php
/**
 * Manage Editions Interface
 * View, edit, and delete editions
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
if (file_exists('../classes/Edition.php')) {
    require_once '../classes/Edition.php';
} else {
    die('Required class files not found. Please check your installation.');
}

$message = '';
$messageType = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        $editionId = filter_var($_POST['edition_id'], FILTER_VALIDATE_INT);
        
        if (!$editionId) {
            throw new Exception('Invalid edition ID');
        }
        
        if (class_exists('Edition')) {
            $editionModel = new Edition();
            
            switch ($action) {
                case 'delete':
                    if ($editionModel->delete($editionId)) {
                        $message = 'Edition deleted successfully';
                        $messageType = 'success';
                    } else {
                        throw new Exception('Failed to delete edition');
                    }
                    break;
                    
                case 'toggle_status':
                    $currentStatus = $_POST['current_status'] ?? '';
                    $newStatus = $currentStatus === 'published' ? 'draft' : 'published';
                    
                    if ($editionModel->update($editionId, ['status' => $newStatus])) {
                        $message = "Edition status changed to $newStatus";
                        $messageType = 'success';
                    } else {
                        throw new Exception('Failed to update edition status');
                    }
                    break;
                    
                default:
                    throw new Exception('Invalid action');
            }
        } else {
            throw new Exception('Edition class not found');
        }
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get editions
$editions = [];
if (class_exists('Edition')) {
    try {
        $editionModel = new Edition();
        // Get all editions (including drafts) for management
        $editions = $editionModel->getAll();
    } catch (Exception $e) {
        error_log('Error fetching editions: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Editions - E-Paper CMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .manage-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }
        
        .header-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }
        
        .editions-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .table tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-published {
            background: #d4edda;
            color: #155724;
        }
        
        .status-draft {
            background: #fff3cd;
            color: #856404;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.5rem 0.75rem;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            margin: 0 0.25rem;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .edition-thumbnail {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .edition-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .edition-meta {
            font-size: 0.8rem;
            color: #666;
        }
        
        .stats {
            font-size: 0.8rem;
            color: #666;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ccc;
        }
        
        @media (max-width: 768px) {
            .manage-container {
                padding: 1rem;
            }
            
            .header-nav {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .table-responsive {
                overflow-x: auto;
            }
            
            .table {
                min-width: 600px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="manage-container">
            <div class="header-nav">
                <h1><i class="fas fa-newspaper"></i> Manage Editions</h1>
                <div>
                    <a href="upload.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Edition
                    </a>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>
            
            <div class="editions-table">
                <?php if (!empty($editions)): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Edition</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Stats</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($editions as $edition): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <img src="../<?php echo $edition['thumbnail_path'] ?? 'assets/images/placeholder.png'; ?>" 
                                             alt="Thumbnail" class="edition-thumbnail">
                                        <div>
                                            <div class="edition-title"><?php echo htmlspecialchars($edition['title']); ?></div>
                                            <div class="edition-meta">
                                                <?php if (!empty($edition['description'])): ?>
                                                <?php echo htmlspecialchars(substr($edition['description'], 0, 60)); ?>
                                                <?php if (strlen($edition['description']) > 60) echo '...'; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div><?php echo date('M j, Y', strtotime($edition['date'])); ?></div>
                                    <div class="edition-meta">
                                        Created: <?php echo date('M j', strtotime($edition['created_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $edition['status']; ?>">
                                        <?php echo ucfirst($edition['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="stats">
                                        <div><i class="fas fa-eye"></i> <?php echo number_format($edition['views'] ?? 0); ?> views</div>
                                        <div><i class="fas fa-download"></i> <?php echo number_format($edition['downloads'] ?? 0); ?> downloads</div>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                                        <a href="../?id=<?php echo $edition['id']; ?>" 
                                           class="btn btn-primary" title="View Edition">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="edition_id" value="<?php echo $edition['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $edition['status']; ?>">
                                            <button type="submit" 
                                                    class="btn <?php echo $edition['status'] === 'published' ? 'btn-warning' : 'btn-success'; ?>"
                                                    title="<?php echo $edition['status'] === 'published' ? 'Unpublish' : 'Publish'; ?>">
                                                <i class="fas fa-<?php echo $edition['status'] === 'published' ? 'eye-slash' : 'check'; ?>"></i>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Are you sure you want to delete this edition? This action cannot be undone.')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="edition_id" value="<?php echo $edition['id']; ?>">
                                            <button type="submit" class="btn btn-danger" title="Delete Edition">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-newspaper"></i>
                    <h3>No Editions Found</h3>
                    <p>You haven't created any editions yet.</p>
                    <a href="upload.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Your First Edition
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
