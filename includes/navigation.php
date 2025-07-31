<?php
// Get categories for navigation
if (!isset($categoryModel)) {
    $categoryModel = new Category();
}
$navCategories = $categoryModel->getActive();
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-newspaper me-2"></i>E-Paper
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="index.php">
                        <i class="fas fa-home me-1"></i>Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'archive.php' ? 'active' : '' ?>" href="archive.php">
                        <i class="fas fa-archive me-1"></i>Archive
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : '' ?>" href="categories.php">
                        <i class="fas fa-folder me-1"></i>Categories
                    </a>
                </li>
            </ul>
            
            <!-- Category Dropdown -->
            <?php if (!empty($navCategories)): ?>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-filter me-1"></i>Browse by Category
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="archive.php">
                            <i class="fas fa-th-large me-2"></i>All Categories
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php foreach ($navCategories as $cat): ?>
                        <li>
                            <a class="dropdown-item" href="archive.php?category=<?= urlencode($cat['slug']) ?>">
                                <i class="<?= htmlspecialchars($cat['icon']) ?> me-2" style="color: <?= htmlspecialchars($cat['color']) ?>;"></i>
                                <?= htmlspecialchars($cat['name']) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>
