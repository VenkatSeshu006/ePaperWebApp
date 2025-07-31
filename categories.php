<?php
require_once 'includes/db.php';

// Initialize classes
$categoryModel = new Category();
$editionModel = new Edition();

// Get all active categories with edition counts
$categories = $categoryModel->getWithCounts();

// Get recent editions for featured section
$recentEditions = $editionModel->getFeatured(6);

// Page title
$pageTitle = 'Browse by Categories';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - E-Paper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .category-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: 100%;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .category-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        
        .category-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .category-count {
            color: #666;
            font-size: 0.9rem;
        }
        
        .category-description {
            color: #777;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 3rem;
        }
        
        .recent-editions .card {
            transition: transform 0.3s ease;
        }
        
        .recent-editions .card:hover {
            transform: translateY(-3px);
        }
        
        .breadcrumb-item a {
            text-decoration: none;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-newspaper me-2"></i>E-Paper
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="archive.php">Archive</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="categories.php">Categories</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 mb-3">Browse by Categories</h1>
            <p class="lead">Explore our content organized by topics and themes</p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Categories</li>
            </ol>
        </nav>

        <!-- Categories Grid -->
        <div class="row">
            <?php if (empty($categories)): ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                        <h3 class="text-muted">No Categories Available</h3>
                        <p class="text-muted">Categories will appear here once they are created.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($categories as $category): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <a href="archive.php?category=<?= urlencode($category['slug']) ?>" class="text-decoration-none">
                            <div class="card category-card h-100">
                                <div class="card-body text-center p-4">
                                    <div class="category-icon" style="background-color: <?= htmlspecialchars($category['color']) ?>20; color: <?= htmlspecialchars($category['color']) ?>;">
                                        <i class="<?= htmlspecialchars($category['icon']) ?>"></i>
                                    </div>
                                    <h5 class="category-name"><?= htmlspecialchars($category['name']) ?></h5>
                                    <p class="category-count mb-2">
                                        <i class="fas fa-newspaper me-1"></i>
                                        <?= $category['edition_count'] ?> 
                                        <?= $category['edition_count'] == 1 ? 'edition' : 'editions' ?>
                                    </p>
                                    <?php if (!empty($category['description'])): ?>
                                        <p class="category-description"><?= htmlspecialchars($category['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Recent Editions Section -->
        <?php if (!empty($recentEditions)): ?>
            <hr class="my-5">
            <div class="recent-editions">
                <div class="row align-items-center mb-4">
                    <div class="col">
                        <h2>Recent Editions</h2>
                        <p class="text-muted mb-0">Latest published content across all categories</p>
                    </div>
                    <div class="col-auto">
                        <a href="archive.php" class="btn btn-outline-primary">
                            View All <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
                
                <div class="row">
                    <?php foreach ($recentEditions as $edition): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card">
                                <?php if (!empty($edition['thumbnail_path'])): ?>
                                    <img src="<?= htmlspecialchars($edition['thumbnail_path']) ?>" 
                                         class="card-img-top" 
                                         alt="<?= htmlspecialchars($edition['title']) ?>"
                                         style="height: 200px; object-fit: cover;">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <a href="view.php?id=<?= $edition['id'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($edition['title']) ?>
                                        </a>
                                    </h6>
                                    <p class="card-text">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?= date('M j, Y', strtotime($edition['publication_date'])) ?>
                                        </small>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-newspaper me-2"></i>E-Paper</h5>
                    <p class="mb-0">Your digital newspaper platform</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <a href="index.php" class="text-light text-decoration-none me-3">Home</a>
                        <a href="archive.php" class="text-light text-decoration-none me-3">Archive</a>
                        <a href="categories.php" class="text-light text-decoration-none">Categories</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
