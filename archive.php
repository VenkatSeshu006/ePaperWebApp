<?php
/**
 * Archive Page
 * Browse all published editions
 */

require_once 'includes/database.php';

// Check if classes exist
if (file_exists('classes/Edition.php')) {
    require_once 'classes/Edition.php';
}
if (file_exists('classes/Category.php')) {
    require_once 'classes/Category.php';
}

// Get parameters
$page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT) : 1;
$category = isset($_GET['category']) ? filter_var($_GET['category'], FILTER_SANITIZE_STRING) : null;
$search = isset($_GET['search']) ? filter_var($_GET['search'], FILTER_SANITIZE_STRING) : null;

$page = max(1, $page);
$itemsPerPage = 12;

// Get editions
$editions = [];
$totalEditions = 0;

if (class_exists('Edition')) {
    try {
        $editionModel = new Edition();
        $editions = $editionModel->getPublished($page, $itemsPerPage, $category);
        $totalEditions = $editionModel->getTotalCount();
    } catch (Exception $e) {
        error_log('Error fetching editions: ' . $e->getMessage());
    }
}

// Get categories
$categories = [];
if (class_exists('Category')) {
    try {
        $categoryModel = new Category();
        $categories = $categoryModel->getWithCounts();
    } catch (Exception $e) {
        error_log('Error fetching categories: ' . $e->getMessage());
    }
}

$totalPages = ceil($totalEditions / $itemsPerPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edition Archive - Digital E-Paper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .archive-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }
        
        .archive-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .archive-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .search-box {
            flex: 1;
            min-width: 300px;
        }
        
        .search-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .category-filter {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .category-btn {
            padding: 0.5rem 1rem;
            border: 2px solid #ddd;
            border-radius: 20px;
            background: white;
            color: #666;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .category-btn.active,
        .category-btn:hover {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
        }
        
        .editions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .edition-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s;
        }
        
        .edition-card:hover {
            transform: translateY(-5px);
        }
        
        .edition-thumbnail {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .edition-content {
            padding: 1.5rem;
        }
        
        .edition-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .edition-date {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .edition-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: #999;
            margin-bottom: 1rem;
        }
        
        .edition-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .page-btn {
            padding: 0.5rem 1rem;
            border: 2px solid #ddd;
            border-radius: 6px;
            background: white;
            color: #666;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .page-btn.active,
        .page-btn:hover {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
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
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/navigation.php'; ?>

    <!-- Archive Content -->
    <div class="container mt-4">
        <div class="archive-container">
            <div class="archive-header">
                <h1>Edition Archive</h1>
                <p>Browse all published newspaper editions</p>
            </div>
            
            <!-- Filters -->
            <div class="archive-filters">
                <div class="search-box">
                    <form method="GET" style="margin: 0;">
                        <input type="search" name="search" class="search-input" 
                               placeholder="Search editions..." 
                               value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        <?php if ($category): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="category-filter">
                    <a href="archive.php" class="category-btn <?php echo !$category ? 'active' : ''; ?>">
                        All
                    </a>
                    <?php foreach ($categories as $cat): ?>
                    <a href="archive.php?category=<?php echo urlencode($cat['slug']); ?>" 
                       class="category-btn <?php echo $category === $cat['slug'] ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Editions Grid -->
            <?php if (!empty($editions)): ?>
            <div class="editions-grid">
                <?php foreach ($editions as $edition): ?>
                <div class="edition-card">
                    <img src="<?php echo $edition['thumbnail_path'] ?? 'assets/images/placeholder.png'; ?>" 
                         alt="<?php echo htmlspecialchars($edition['title']); ?>" 
                         class="edition-thumbnail">
                    <div class="edition-content">
                        <h3 class="edition-title"><?php echo htmlspecialchars($edition['title']); ?></h3>
                        <div class="edition-date">
                            <i class="fas fa-calendar"></i> 
                            <?php echo date('F j, Y', strtotime($edition['date'])); ?>
                        </div>
                        <div class="edition-meta">
                            <span><i class="fas fa-eye"></i> <?php echo number_format($edition['views']); ?></span>
                            <span><i class="fas fa-download"></i> <?php echo number_format($edition['downloads'] ?? 0); ?></span>
                        </div>
                        <div class="edition-actions">
                            <a href="?id=<?php echo $edition['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> Read
                            </a>
                            <?php if ($edition['pdf_path']): ?>
                            <a href="<?php echo $edition['pdf_path']; ?>" class="btn btn-secondary" download>
                                <i class="fas fa-download"></i> PDF
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo $category ? '&category=' . urlencode($category) : ''; ?>" 
                   class="page-btn">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo $category ? '&category=' . urlencode($category) : ''; ?>" 
                   class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo $category ? '&category=' . urlencode($category) : ''; ?>" 
                   class="page-btn">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-newspaper"></i>
                <h3>No Editions Found</h3>
                <p>No editions match your current filters.</p>
                <a href="archive.php" class="btn btn-primary">View All Editions</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
